<?php

namespace ItmoSoft\Promotions\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ItmoSoft\Promotions\PromotionsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PromotionsServiceProvider::class];
    }
}
