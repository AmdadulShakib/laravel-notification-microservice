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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('type', ['sms', 'email', 'whatsapp']);
            $table->string('recipient');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
            $table->unsignedInteger('retry_count')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Composite indexes for performance (50k+ daily notifications)
            $table->index(['status', 'type']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
