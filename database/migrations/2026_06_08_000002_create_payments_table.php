<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('PEN');
            $table->string('method'); // efectivo, transferencia, yape, plin, tarjeta, otro
            $table->string('reference')->nullable(); // nro operacion / voucher
            $table->date('paid_at');
            $table->unsignedSmallInteger('months')->default(1); // meses cubiertos
            $table->string('status')->default('confirmed'); // pending, confirmed, refunded
            $table->text('notes')->nullable();

            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'paid_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
