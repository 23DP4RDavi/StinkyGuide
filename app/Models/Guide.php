<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'game', 'title', 'body', 'type', 'seeded_helpful', 'seeded_author'])]
class Guide extends Model
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GuideComment::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(GuideVote::class);
    }

    public function saves(): HasMany
    {
        return $this->hasMany(SavedGuide::class);
    }
}
