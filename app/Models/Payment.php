<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
     protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_cents'   => 'integer',
            'received_cents' => 'integer',
            'change_cents'   => 'integer',
            'fee_cents'      => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
