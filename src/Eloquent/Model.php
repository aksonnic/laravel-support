<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel {
    use AutosavesRelations,
        HasTraits,
        Naming,
        NestedAttributes,
        TransactionalAwareEvents,
        Transactions,
        Translation,
        Validation {
            AutosavesRelations::validateAutosavedRelations insteadof Validation;
            AutosavesRelations::validationRulesToIgnoreForParentRelations insteadof Validation;
        }

    protected $guarded = [];

    public function __construct($attributes = []) {
        $this->bootIfNotBooted();
        $this->initializeTraits();
        $this->syncOriginal();
        $this->fill($attributes);
    }

    protected function processRollback() {
        $this->rollbackSelfAndAutosavedRelations();
    }

    protected function processSave($options) {
        $this->validate();

        return $this->pushSelfAndAutosavedRelations($options);
    }
}
