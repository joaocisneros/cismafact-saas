<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);
            $table->string('method', 10);
            $table->string('route_name')->nullable();
            $table->string('path');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description')->nullable();
            $table->json('data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_idx');
            $table->index(['company_id', 'created_at'], 'audit_logs_company_created_idx');
            $table->index(['action', 'created_at'], 'audit_logs_action_created_idx');
            $table->index(['subject_type', 'subject_id'], 'audit_logs_subject_idx');
            $table->index('created_at', 'audit_logs_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
