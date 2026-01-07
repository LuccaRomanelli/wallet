<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->integer('amount');
            $table->enum('status', ['pending', 'completed', 'failed']);
            $table->string('failure_reason')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('client_ip', 45);
            $table->text('user_agent')->nullable();
            $table->uuid('request_id')->unique();
            $table->json('authorization_response')->nullable();
            $table->timestamp('created_at', 6)->nullable();
            $table->timestamp('updated_at', 6)->nullable();

            $table->index('payer_id');
            $table->index('payee_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_audit_logs');
    }
};
