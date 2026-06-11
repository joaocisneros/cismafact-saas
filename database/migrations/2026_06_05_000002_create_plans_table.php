<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->unsignedInteger('monthly_document_limit')->default(0);
            $table->unsignedInteger('user_limit')->default(0);
            $table->unsignedInteger('api_request_limit')->default(0);
            $table->string('support_included')->default('Basico');
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        DB::table('plans')->insert([
            [
                'name' => 'Free',
                'code' => 'free',
                'monthly_document_limit' => 50,
                'user_limit' => 1,
                'api_request_limit' => 1000,
                'support_included' => 'Comunidad',
                'monthly_price' => 0,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro',
                'code' => 'pro',
                'monthly_document_limit' => 500,
                'user_limit' => 5,
                'api_request_limit' => 10000,
                'support_included' => 'Correo',
                'monthly_price' => 49,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business',
                'code' => 'business',
                'monthly_document_limit' => 3000,
                'user_limit' => 20,
                'api_request_limit' => 50000,
                'support_included' => 'Prioritario',
                'monthly_price' => 149,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('plan_id')
                ->nullable()
                ->after('id')
                ->constrained('plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });

        Schema::dropIfExists('plans');
    }
};
