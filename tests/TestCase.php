<?php

namespace DoITs\EasyAuth\Tests;

use DoITs\EasyAuth\EasyAuthServiceProvider;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [EasyAuthServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        // Stands in for the host application's own resources/views/layouts/app.blade.php,
        // which every package view extends but this package intentionally never provides.
        $app['view']->addLocation(__DIR__.'/Fixtures/views');
    }

    /**
     * Define the minimal "home" route that any host application is expected
     * to provide, since this package's controllers redirect there.
     */
    protected function defineRoutes($router): void
    {
        $router->get('/', fn () => 'home')->middleware('web')->name('home');
    }

    /**
     * Build the host application's `users` table (which this package never
     * owns) plus this package's own migrations, ahead of each test.
     */
    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Includes this package's own migration that relaxes email/password
        // to nullable, exercising it the same way a host application would.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
