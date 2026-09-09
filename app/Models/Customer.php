<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Customer extends Model
{
    protected $guarded = ['id'];

public function business(): BelongsTo { return $this->belongsTo(Business::class); }
public function sales(): HasMany      { return $this->hasMany(Sale::class); }
}
