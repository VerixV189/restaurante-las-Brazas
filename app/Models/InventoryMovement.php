<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class InventoryMovement extends Model
{
    use LogsActivity;

    protected $fillable = [
        'ingredient_id',
        'user_id',
        'movement_type',
        'quantity',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
