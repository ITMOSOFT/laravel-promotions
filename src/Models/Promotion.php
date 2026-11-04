<?php

namespace ItmoSoft\Promotions\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'rules' => 'array', 'is_active' => 'boolean', 'value' => 'decimal:4', 'minimum_order_amount' => 'decimal:2', 'maximum_discount_amount' => 'decimal:2'];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(config('promotions.models.redemption', Redemption::class), 'promotion_id');
    }

    public function getTable(): string
    {
        return (string) config('promotions.table_prefix', 'promotion_').'promotions';
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    public function isWithinSchedule(?\DateTimeInterface $at = null): bool
    {
        $at = $at ? \Illuminate\Support\Carbon::parse($at) : now();
        return $this->is_active
            && (!$this->starts_at || $this->starts_at->lte($at))
            && (!$this->ends_at || $this->ends_at->gte($at));
    }

    public function tableName(string $name): string
    {
        return (string) config('promotions.table_prefix', 'promotion_').$name;
    }
}
