<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Subscriptions\SubscriptionUsageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

class TestSubscriptionSystem extends Command
{
    protected SubscriptionUsageService $usageService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-subscription-system {user_email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test subscription system implementation';
    public function __construct(SubscriptionUsageService $usageService)
    {
        parent::__construct();
        $this->usageService = $usageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Subscription System...');
        $this->newLine();

        // Test 1: Check tables exist
        $this->testDatabaseTables();

        // Test 2: Check User model has Billable trait
        $this->testUserModel();

        // Test 3: Check configuration
        $this->testConfiguration();

        // Test 4: Check routes
        $this->testRoutes();

        // Test 5: Check middleware
        $this->testMiddleware();

        // Test 6: Check services
        $this->testServices();

        // Test 7: Test with actual user (if provided)
        if ($email = $this->argument('user_email')) {
            $this->testWithUser($email);
        }

        $this->newLine();
        $this->info('✅ All tests completed!');
    }

    protected function testDatabaseTables()
    {
        $this->info('1. Testing Database Tables...');

        $tables = [
            'subscriptions',
            'subscription_items',
            'subscription_usage',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->line("   ✓ Table '{$table}' exists");
            } else {
                $this->error("   ✗ Table '{$table}' is missing!");
            }
        }

        $this->newLine();
    }

    protected function testUserModel()
    {
        $this->info('2. Testing User Model...');

        $user = new User();
        
        // Check if Billable trait is used
        $traits = class_uses_recursive(User::class);
        
        if (in_array('Laravel\Cashier\Billable', $traits)) {
            $this->line('   ✓ User model uses Billable trait');
            
            // Check available methods
            $methods = [
                'subscribed',
                'subscription',
                'subscribedToPrice',
                'createSetupIntent',
                'newSubscription',
                'invoices',
            ];

            foreach ($methods as $method) {
                if (method_exists($user, $method)) {
                    $this->line("   ✓ Method '{$method}' available");
                } else {
                    $this->error("   ✗ Method '{$method}' not found!");
                }
            }
        } else {
            $this->error('   ✗ User model does not use Billable trait!');
        }

        $this->newLine();
    }

    protected function testConfiguration()
    {
        $this->info('3. Testing Configuration...');

        // Check .env variables
        $envVars = [
            'STRIPE_KEY',
            'STRIPE_SECRET',
        ];

        foreach ($envVars as $var) {
            if (config("cashier.key") && $var === 'STRIPE_KEY') {
                $this->line("   ✓ {$var} is configured");
            } elseif (config("cashier.secret") && $var === 'STRIPE_SECRET') {
                $this->line("   ✓ {$var} is configured");
            } else {
                $this->warn("   ⚠ {$var} not configured");
            }
        }

        // Check subscription plans
        $plans = config('subscriptions.plans');
        if ($plans && is_array($plans)) {
            $this->line("   ✓ Subscription plans configured (" . count($plans) . " plans)");
            foreach ($plans as $key => $plan) {
                $this->line("     - {$plan['name']} (\${$plan['price']}/month)");
            }
        } else {
            $this->error('   ✗ Subscription plans not configured!');
        }

        $this->newLine();
    }

    protected function testRoutes()
    {
        $this->info('4. Testing Routes...');

        $routes = [
            'subscriptions.plans' => 'GET',
            'subscriptions.status' => 'GET',
            'subscriptions.usage' => 'GET',
            'subscriptions.intent' => 'POST',
            'subscriptions.subscribe' => 'POST',
            'subscriptions.cancel' => 'POST',
            'cashier.webhook' => 'POST',
        ];

        foreach ($routes as $name => $method) {
            if (Route::has($name)) {
                $route = Route::getRoutes()->getByName($name);
                $this->line("   ✓ Route '{$name}' [{$method}] -> " . $route->uri());
            } else {
                $this->error("   ✗ Route '{$name}' not found!");
            }
        }

        $this->newLine();
    }

    protected function testMiddleware()
    {
        $this->info('5. Testing Middleware...');

        $middlewareAliases = app('router')->getMiddleware();

        if (isset($middlewareAliases['subscribed'])) {
            $this->line("   ✓ Middleware 'subscribed' registered");
            $this->line("     Class: " . $middlewareAliases['subscribed']);
        } else {
            $this->error("   ✗ Middleware 'subscribed' not registered!");
        }

        $this->newLine();
    }

    protected function testServices()
    {
        $this->info('6. Testing Services...');

        try {
            $service = $this->usageService;
            $this->line('   ✓ SubscriptionUsageService can be resolved');
            // Check service methods
            $methods = [
                'getUsage',
                'canUse',
                'incrementUsage',
                'getUserUsageStats',
            ];

            foreach ($methods as $method) {
                if (method_exists($service, $method)) {
                    $this->line("   ✓ Method '{$method}' exists");
                } else {
                    $this->error("   ✗ Method '{$method}' not found!");
                }
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Failed to resolve SubscriptionUsageService: ' . $e->getMessage());
        }

        $this->newLine();
    }

    protected function testWithUser(string $email)
    {
        $this->info("7. Testing with User: {$email}");

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("   ✗ User not found with email: {$email}");
            return;
        }

        $this->line("   ✓ User found: {$user->name} (ID: {$user->id})");

        // Check subscription status
        if ($user->subscribed('default')) {
            $this->line('   ✓ User has active subscription');
            
            $subscription = $user->subscription('default');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Stripe ID', $subscription->stripe_id],
                    ['Status', $subscription->stripe_status],
                    ['Price ID', $subscription->stripe_price],
                    ['Created', $subscription->created_at],
                    ['Ends At', $subscription->ends_at ?? 'N/A'],
                ]
            );

            // Check usage
            try {
                $stats = $this->usageService->getUserUsageStats($user);
                $this->line('   ✓ Usage statistics available');
                
                $this->table(
                    ['Feature', 'Used', 'Limit', 'Remaining'],
                    collect($stats['usage'])->map(function ($data, $feature) {
                        return [
                            $feature,
                            $data['used'] ?? 'N/A',
                            $data['unlimited'] ? 'Unlimited' : ($data['limit'] ?? 'N/A'),
                            $data['unlimited'] ? '∞' : ($data['remaining'] ?? 'N/A'),
                        ];
                    })->toArray()
                );
            } catch (\Exception $e) {
                $this->error('   ✗ Failed to get usage stats: ' . $e->getMessage());
            }

        } else {
            $this->warn('   ⚠ User does not have an active subscription');
            
            // Check if user ever had a subscription
            $hasSubscriptions = $user->subscriptions()->exists();
            if ($hasSubscriptions) {
                $this->line('   ℹ User has subscription history');
                $allSubs = $user->subscriptions;
                foreach ($allSubs as $sub) {
                    $this->line("     - {$sub->name}: {$sub->stripe_status} (Ends: " . ($sub->ends_at ?? 'N/A') . ")");
                }
            } else {
                $this->line('   ℹ User has never had a subscription');
            }
        }

        $this->newLine();
    }
}
