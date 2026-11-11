<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

abstract class IsolatedMySqlTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUpTraits()
    {
        $connection = config('database.default');
        $settings = config("database.connections.{$connection}", []);

        if (
            !app()->environment('testing')
            || $connection !== 'mysql'
            || !in_array($settings['host'] ?? null, ['127.0.0.1', 'localhost', '::1'], true)
            || ($settings['database'] ?? null) !== 'rentrack_v2_testing'
            || ($settings['username'] ?? null) !== 'rentrack_test'
        ) {
            throw new LogicException(
                'Database tests require the approved local rentrack_v2_testing database and rentrack_test account.'
            );
        }

        return parent::setUpTraits();
    }

    protected function activeAdmin(): Account
    {
        return Account::factory()->forAdmin(Admin::factory()->create())->create();
    }

    protected function activeTenant(): Account
    {
        return Account::factory()->forTenant(Tenant::factory()->create())->create();
    }
}
