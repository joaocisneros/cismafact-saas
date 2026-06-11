<?php

namespace Tests\Unit;

use App\Models\Subscription;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    public function test_active_subscription_with_current_dates_allows_access(): void
    {
        $subscription = new Subscription([
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $this->assertTrue($subscription->allowsAccess());
    }

    public function test_expired_or_suspended_subscription_denies_access(): void
    {
        $expired = new Subscription([
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        $suspended = new Subscription([
            'status' => 'suspended',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertFalse($expired->allowsAccess());
        $this->assertFalse($suspended->allowsAccess());
    }
}
