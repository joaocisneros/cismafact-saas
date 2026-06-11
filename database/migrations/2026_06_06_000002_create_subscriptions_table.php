<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('active');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->date('next_billing_at')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->boolean('auto_renew')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at'], 'subscriptions_status_end_idx');
            $table->index(['plan_id', 'status'], 'subscriptions_plan_status_idx');
            $table->index('next_billing_at', 'subscriptions_next_billing_idx');
        });

        $companies = DB::table('companies')
            ->whereNotNull('plan_id')
            ->get(['id', 'plan_id', 'activo', 'created_at']);

        foreach ($companies as $company) {
            $price = DB::table('plans')->where('id', $company->plan_id)->value('monthly_price') ?? 0;
            $start = $company->created_at ?: now();

            DB::table('subscriptions')->insert([
                'company_id' => $company->id,
                'plan_id' => $company->plan_id,
                'status' => $company->activo ? 'active' : 'suspended',
                'starts_at' => $start,
                'ends_at' => null,
                'next_billing_at' => $price > 0 ? now()->addMonth()->toDateString() : null,
                'monthly_price' => $price,
                'auto_renew' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
