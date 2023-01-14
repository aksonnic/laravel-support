<?php

use App\Models\Device;
use Orchestra\Testbench\TestCase;

require_once __DIR__ . '/../models/Device.php';

class FluentModelTest extends TestCase {

    public function setUp() {
        $this->device = new Device([
            'model' => 'iPhone',
            'version' => '14'
        ]);
    }

    public function testFluentModelComputedAttributes() {
        $this->assertEquals('Apple', $this->device->vendor);
    }

    public function testFluentModelMutators() {
        $this->device->type = '12 Pro Max';
        $this->assertEquals('12 Pro Max', $this->device->model);
    }


    public function testFluentModelNaming() {
        $this->assertEquals('device', $this->device->model_name->singular);
    }
}
