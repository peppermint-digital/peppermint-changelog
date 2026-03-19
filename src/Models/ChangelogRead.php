<?php

namespace Peppermint\Changelog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangelogRead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'changelog_slug',
        'read_at',
        'modal_dismissed',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'modal_dismissed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        $userModel = config('changelog.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel);
    }
}
