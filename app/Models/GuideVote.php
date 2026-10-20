<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'guide_id'])]
class GuideVote extends Model
{
    public $timestamps = false;

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }
}
