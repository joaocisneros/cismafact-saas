<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('subject');
                $table->text('message');
                $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->timestamps();

                $table->index('status');
                $table->index('priority');
            });
        }

        if (!Schema::hasTable('ticket_replies')) {
            Schema::create('ticket_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('message');
                $table->boolean('is_admin')->default(false);
                $table->timestamps();

                $table->index('ticket_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
    }
};
