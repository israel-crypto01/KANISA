<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'user_id',
        'message',
        'recipients_count',
        'sent_count',
        'failed_count',
        'status',         // sent, partial, failed, pending
        'beem_response',  // Raw JSON response from Beem Africa
        'error_message',  // Error message if failed
    ];

    protected $casts = [
        'beem_response' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Human-readable status badge colour for the UI */
    public function statusColor(): string
    {
        return match ($this->status) {
            'sent'    => 'green',
            'partial' => 'yellow',
            'failed'  => 'red',
            default   => 'gray',
        };
    }
}
