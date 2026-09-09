<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class CashSession extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'opened_at'        => 'datetime',
            'closed_at'        => 'datetime',
            'opening_cents'    => 'integer',
            'expected_cents'   => 'integer',
            'counted_cents'    => 'integer',
            'difference_cents' => 'integer',
        ];
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
