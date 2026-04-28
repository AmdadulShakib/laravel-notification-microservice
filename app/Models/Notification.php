<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'type',
        'recipient',
        'message',
        'metadata',
        'status',
        'retry_count',
        'response_time_ms',
        'sent_at',
        'failed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'user_id' => 'integer',
            'retry_count' => 'integer',
            'response_time_ms' => 'integer',
        ];
    }

    /**
     * Get the AI/Analytics logs for this notification.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by type (sms, email, whatsapp)
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Check if the notification can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    /**
     * Mark notification as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark notification as sent.
     */
    public function markAsSent(int $responseTimeMs): void
    {
        $this->update([
            'status' => 'sent',
            'response_time_ms' => $responseTimeMs,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'retry_count' => $this->retry_count + 1,
            'failed_at' => now(),
        ]);
    }
}
