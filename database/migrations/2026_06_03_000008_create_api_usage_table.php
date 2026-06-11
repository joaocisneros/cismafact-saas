<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 10);
            $table->string('path');
            $table->smallInteger('status_code');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('request_body')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('created_at');

            $table->index(['company_id', 'created_at']);
            $table->index('api_key_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage');
    }
};
