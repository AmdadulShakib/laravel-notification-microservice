<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * AI/Analytics structured logs for training data (delivery success prediction, churn analysis)
     */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->onDelete('cascade');
            $table->string('type', 20); // sms, email, whatsapp
            $table->string('status', 20); // pending, processing, sent, failed
            $table->unsignedInteger('retry_count')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('channel_response')->nullable(); // Simulated channel response
            $table->timestamp('created_at')->useCurrent();

            // Indexes for AI training data queries
            $table->index(['notification_id', 'created_at']);
            $table->index(['type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
