<?php

namespace ItmoSoft\Promotions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Redemption extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['discount_amount' => 'decimal:2', 'subtotal' => 'decimal:2', 'context' => 'array', 'redeemed_at' => 'datetime'];
    }

    public function getTable(): string
    {
        return (string) config('promotions.table_prefix', 'promotion_').'redemptions';
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(config('promotions.models.promotion', Promotion::class), 'promotion_id');
    }

    public function customer(): MorphTo
    {
        return $this->morphTo();
    }
}
