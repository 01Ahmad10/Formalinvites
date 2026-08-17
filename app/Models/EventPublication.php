<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class EventPublication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'published_at' => 'immutable_datetime'];
    }

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function publishedBy(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Published invitation versions are immutable. Publish a new version instead.'));
        static::deleting(fn () => throw new LogicException('Published invitation versions cannot be deleted.'));
    }
}
