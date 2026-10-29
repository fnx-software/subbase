<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests;

use Nafiswatsiq\Subbase\SubbaseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SubbaseServiceProvider::class];
    }
}
