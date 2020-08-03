<?php

namespace Booni3\DynamicWms\Tests;

use Orchestra\Testbench\TestCase;
use Booni3\DynamicWms\DynamicWmsServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [DynamicWmsServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
