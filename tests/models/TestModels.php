<?php

namespace App\Models;

use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class Account extends Model {

    public $timestamps = false;

    public function company() {
        return $this->belongsTo(Company::class);
    }

    protected function initializeTraits() {
        parent::initializeTraits();

        $this->addValidationRules('name', ['required']);
    }
}

class Company extends Model {

    public $timestamps = false;

    public function account() {
        return $this->hasOne(Account::class);
    }

    protected function initializeTraits() {
        parent::initializeTraits();

        $this->addAutosavedRelation('account');

        $this->addValidationRules('name', ['required']);
    }
}

class Eye extends Model {

    public $timestamps = false;

    public $createdFlagStack = [];

    public function iris() {
        return $this->hasOne(Iris::class);
    }

    public function retina() {
        return $this->hasOne(Retina::class);
    }

    public function permanent_iris() {
        return $this->hasOne(Iris::class);
    }

    public function update_only_iris() {
        return $this->hasOne(Iris::class);
    }

    public function update_and_destroy_iris() {
        return $this->hasOne(Iris::class);
    }

    protected static function bootTraits() {
        Eye::created(function($eye) {
            $eye->createdFlagStack[] = $eye->iris
                ? !$eye->iris->exists
                : 'UNSET';
        });

        parent::bootTraits();

        Eye::created(function($eye) {
            $eye->createdFlagStack[] = $eye->iris
                ? !$eye->iris->exists
                : 'UNSET';
        });
    }

    protected function initializeTraits() {
        parent::initializeTraits();

        $this->addAcceptedNestedAttribute('iris', ['allow_destroy' => true]);
        $this->addAcceptedNestedAttribute('permanent_iris', ['allow_destroy' => false]);
        $this->addAcceptedNestedAttribute('update_only_iris', ['update_only' => true]);
        $this->addAcceptedNestedAttribute('update_and_destroy_iris', ['update_only' => true, 'allow_destroy' => true]);
    }
}

class Iris extends Model {
    public $timestamps = false;

    public function eye() {
        return $this->belongsTo(Eye::class);
    }
}

class Retina extends Model {
    public $timestamps = false;

    public function eye() {
        return $this->belongsTo(Eye::class);
    }

    public function permanent_eye() {
        return $this->belongsTo(Eye::class, 'eye_id');
    }

    public function update_only_eye() {
        return $this->belongsTo(Eye::class, 'eye_id');
    }

    public function update_and_destroy_eye() {
        return $this->belongsTo(Eye::class, 'eye_id');
    }

    protected function initializeTraits() {
        parent::initializeTraits();

        $this->addAcceptedNestedAttribute('eye', ['allow_destroy' => true]);
        $this->addAcceptedNestedAttribute('permanent_eye', ['allow_destroy' => false]);
        $this->addAcceptedNestedAttribute('update_only_eye', ['update_only' => true]);
        $this->addAcceptedNestedAttribute('update_and_destroy_eye', ['allow_destroy' => true, 'update_only' => true]);
    }
}

class Customer extends Model {
    public $timestamps = false;

    public function taggings() {
        return $this->morphMany(Tagging::class, 'taggable');
    }
}

class Order extends Model {
    public $timestamps = false;

    public function billing() {
        return $this->belongsTo(Customer::class, 'billing_customer_id');
    }

    public function shipping() {
        return $this->belongsTo(Customer::class, 'shipping_customer_id');
    }

    public function nested_billing() {
        return $this->belongsTo(Customer::class, 'billing_customer_id');
    }

    protected function initializeTraits() {
        parent::initializeTraits();

        $this->addAutosavedRelation(['billing', 'shipping']);
        $this->addAcceptedNestedAttribute(['nested_billing']);
    }
}

class Tag extends Model {
    public $timestamps = false;

    public function taggings() {
        return $this->hasMany(Tagging::class);
    }
}

class Tagging extends Model {
    public $timestamps = false;

    public function tag() {
        return $this->belongsTo(Tag::class);
    }

    public function taggable() {
        return $this->morphTo();
    }

    protected function initializeTraits() {
        parent::initializeTraits();

        $this->addAutosavedRelation('taggable');
    }
}

class Guitar extends Model {
    public $timestamps = false;

    public $firedEvents = [];

    protected function fireModelEvent($event, $halt = true) {
        $this->firedEvents[] = $event;

        parent::fireModelEvent($event, $halt);
    }
}

class InvalidNestedAttrModel extends Model {

    protected function initializeTraits() {
        parent::initializeTraits();

        $this->addAcceptedNestedAttribute('unknown');
    }
}
