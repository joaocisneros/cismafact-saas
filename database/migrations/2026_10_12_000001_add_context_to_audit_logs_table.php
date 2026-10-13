<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_name', 120)->nullable()->after('user_id');
            $table->unsignedBigInteger('actor_user_id')->nullable()->after('user_id');
            $table->string('actor_email', 150)->nullable()->after('actor_name');
            $table->string('target_email', 150)->nullable()->after('actor_email');
            $table->string('target_name', 180)->nullable()->after('target_email');
            $table->string('service_context', 40)->nullable()->after('description');
            $table->string('environment_context', 20)->nullable()->after('service_context');
            $table->string('event_type', 60)->nullable()->after('environment_context');

            $table->index(['service_context', 'created_at'], 'audit_logs_service_created_idx');
            $table->index(['environment_context', 'created_at'], 'audit_logs_environment_created_idx');
            $table->index('actor_user_id', 'audit_logs_actor_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_service_created_idx');
            $table->dropIndex('audit_logs_environment_created_idx');
            $table->dropIndex('audit_logs_actor_user_idx');
            $table->dropColumn([
                'actor_user_id', 'actor_name', 'actor_email', 'target_email', 'target_name',
                'service_context', 'environment_context', 'event_type',
            ]);
        });
    }
};
