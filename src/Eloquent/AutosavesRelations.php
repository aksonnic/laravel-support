<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

trait AutosavesRelations {

    protected $autosavedRelations = [];
    protected $markedForDestruction = false;

    public function addAutosavedRelation($names) {
        foreach ((array)$names as $name) {
            $opts = [];
            $this->autosavedRelations[$name] = $opts;
        }
    }

    public function isAutosaveRelation($name) {
        return in_array($name, array_keys($this->autosavedRelations));
    }

    public function isMarkedForDestruction() {
        return $this->markedForDestruction;
    }

    public function markForDestruction() {
        $this->markedForDestruction = true;
    }

    public function push() {
        return $this->save();
    }

    public function refresh() {
        $this->markedForDestruction = false;

        return parent::refresh();
    }

    protected function phaseForAutosavedRelation($name) {
        if (!method_exists($this, $name)) {
            throw new RuntimeException('Unknown relation ' . $name . ' on ' . get_class($this));
        }

        $order = 'post';

        $relationType = class_basename($this->{$name}());

        switch ($relationType) {
            case 'BelongsTo':
            case 'MorphTo':
                $order = 'pre';
                break;
            case 'HasOne':
            case 'HasMany':
            case 'MorphOne':
                break;
            default:
                throw new RuntimeException("Autosave for $relationType relations not supported.");
        }

        return $order;
    }

    protected function getAutosaveOptionsFor($relationName) {
        return Arr::get($this->autosavedRelations, $relationName, []);
    }

    protected function getInverseRelationNameFor($relationName) {
        $method = 'inverseRelationNameFor' . Str::studly($relationName);

        return method_exists($this, $method) ? $this->{$method}() : Str::singular($this->getTable());
    }

    protected function loadedAutosavedRelations() {
        return array_filter($this->relations, function ($key) {
            return $this->isAutosaveRelation($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function loadedAutosavedRelationsByOrder() {
        $ret = [
            'pre' => [],
            'post' => [],
        ];

        $loadedRelations = $this->loadedAutosavedRelations();
        foreach ($loadedRelations as $relationName => $value) {
            $order = $this->phaseForAutosavedRelation($relationName);
            $ret[$order][$relationName] = $value;
        }

        return $ret;
    }

    protected function pushAutosavedModels($models, $relationName, $options) {
        $ret = true;

        foreach (array_filter($models ?: []) as $model) {
            if ($model->isMarkedForDestruction() && $model->exists) {
                $ret = $model->delete();
            } else {
                $relation = $this->{$relationName}();
                $relationType = class_basename($relation);

                switch ($relationType) {
                    case 'HasOne':
                    case 'HasMany':
                    case 'MorphOne':
                        $inverseName = $this->getInverseRelationNameFor($relationName);
                        $model->{$inverseName}()->associate($this);
                        $ret = $model->saveOrFail($options);
                        break;
                    case 'BelongsTo':
                    case 'MorphTo':
                        $ret = $model->saveOrFail($options);
                        if ($ret && $model->exists) {
                            $foreignKey = $relation->getForeignKey();
                            $this->{$foreignKey} = $model->getKey();
                            if ($relationType == 'MorphTo') {
                                $this->{$relation->getMorphType()} = $model->getMorphClass();
                            }
                        }
                        break;
                    default:
                        throw new RuntimeException("Autosave for $relationType relations not supported.");
                }
            }

            if (!$ret) {
                return false;
            }
        }

        return $ret;
    }

    protected function pushAutosavedRelation($relationName, $value, $pushOptions) {
        $models = ($value instanceof Collection)
            ? $value->all()
            : ($value instanceof Model ? [$value] : $value);

        if (!$this->pushAutosavedModels($models, $relationName, $pushOptions)) {
            return false;
        }

        return true;
    }

    protected function pushSelfAndAutosavedRelations($options) {
        $relationsToAutosave = $this->loadedAutosavedRelationsByOrder();

        foreach ($relationsToAutosave['pre'] as $relationName => $value) {
            if (!$this->pushAutosavedRelation($relationName, $value, $options)) {
                return false;
            }
        }

        if (!$this->saveSelf($options)) {
            return false;
        }

        foreach ($relationsToAutosave['post'] as $relationName => $value) {
            if (!$this->pushAutosavedRelation($relationName, $value, $options)) {
                return false;
            }
        }

        return true;
    }

    protected function validateAutosavedRelations() {
        $relationsToAutosave = $this->loadedAutosavedRelations();

        foreach ($relationsToAutosave as $relationName => $value) {
            $models = ($value instanceof Collection)
                ? $value->all()
                : ($value instanceof Model ? [$value] : $value);

            $this->validateAutosavedModels($models, $relationName);
        }
    }

    protected function validateAutosavedModels($models, $relationName) {
        $relationType = class_basename($this->{$relationName}());

        foreach (array_filter($models ?: []) as $model) {
            if (!$model->isMarkedForDestruction()) {
                try {
                    $ignoredRules = $this->validationRulesToIgnore($model, $relationName);
                    $model->validate(null, $ignoredRules);
                } catch (ValidationException $vex) {
//                    $this->mergeErrors($model->errors, $relationName);
                    $this->mergeErrors(new MessageBag([
                        $relationName => [Lang::get($this->genericInvalidMessageKey)]
                    ]));
                }
            }
        }
    }

    protected function validationRulesToIgnoreForParentRelations() {
        $ignore = [];
        $relationsToAutosave = $this->loadedAutosavedRelationsByOrder();

        foreach ($relationsToAutosave['pre'] as $relationName => $value) {
            $relation = $this->{$relationName}();
            // old Laravel needs getForeignKey()
            $ignore[] = method_exists($relation, 'getForeignKeyName')
                ? $relation->getForeignKeyName()
                : $relation->getForeignKey();
        }

        return $ignore;
    }

    protected function validationRulesToIgnore($model, $relationName) {
        $ignored = [];

        $relationType = class_basename($this->{$relationName}());

        switch ($relationType) {
            case 'BelongsTo':
            case 'MorphTo':
                break;
            default:
                $inverseName = $this->getInverseRelationNameFor($relationName);
                $inverseRelation = $model->{$inverseName}();
                $inverseRelationType = class_basename(get_class($inverseRelation));
                switch ($inverseRelationType) {
                    case 'MorphTo':
                        $ignored = [$inverseRelation->getForeignKeyName(), $inverseRelation->getMorphType()];
                        break;
                    case 'BelongsTo':
                        $ignored = [$inverseRelation->getForeignKey()];
                        break;
                    default:
                        throw new RuntimeException(
                            'Nested validation for ' . $inverseRelationType
                            . ' (inverse) relations not supported.'
                        );
                        break;
                }
                break;
        }

        return $ignored;
    }

    protected function rollbackAutosavedModels($models, $relationName) {
        foreach (array_filter($models ?: []) as $model) {
            if ($model->isMarkedForDestruction() && $model->id) {
                $model->exists = true;
            } else {
                $model->processRollback();
            }
        }
    }

    protected function rollbackSelfAndAutosavedRelations() {
        $this->rollbackSelf();
        // $relationsToRollback = $this->loadedAutosavedRelations();

        // foreach ($relationsToRollback as $relationName => $value) {
        //     $models = ($value instanceof Collection)
        //         ? $value->all()
        //         : ($value instanceof Model ? [$value] : $value);

        //     $this->rollbackAutosavedModels($models, $relationName);
        // }
    }

    protected function saveSelf($options) {
        return parent::save();
    }
}
