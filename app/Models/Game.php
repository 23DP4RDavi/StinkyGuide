<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'genre', 'steam_app_id', 'tags', 'platform', 'match_score'])]
class Game extends Model
{
    protected function casts(): array
    {
        return ['tags' => 'array'];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function libraryEntries(): HasMany
    {
        return $this->hasMany(GameLibrary::class);
    }
}
