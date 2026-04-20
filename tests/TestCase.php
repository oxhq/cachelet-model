<?php

declare(strict_types=1);

namespace Oxhq\Cachelet\Model\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Oxhq\Cachelet\CacheletModelServiceProvider;
use Oxhq\Cachelet\CacheletServiceProvider;
use Oxhq\Cachelet\Facades\Cachelet;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CacheletServiceProvider::class,
            CacheletModelServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Cachelet' => Cachelet::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cachelet.defaults.ttl', 3600);
        $app['config']->set('cachelet.defaults.prefix', 'cachelet');
        $app['config']->set('cachelet.observability.events.enabled', false);
        $app['config']->set('cachelet.observe.auto_register', true);
        $app['config']->set('cachelet.serialization.exclude_dates', true);
        $app['config']->set('cachelet.serialization.default_excludes', []);
        $app['config']->set('cachelet.serialization.default_only', []);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        Carbon::setTestNow(Carbon::create(2025, 7, 10, 12, 0, 0));
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('dummies', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('role')->nullable();
            $table->timestamps();
            $table->timestamp('email_verified_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
