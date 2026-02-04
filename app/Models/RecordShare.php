<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RecordShare extends Model
{
    protected $fillable = [
        'user_id',
        'target_user_id',
        'shareable_type',
        'shareable_id',
        'expires_at',
        'can_edit',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'can_edit' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
