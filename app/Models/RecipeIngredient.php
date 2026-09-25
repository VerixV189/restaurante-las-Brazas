<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class RecipeIngredient extends Model
{
    use LogsActivity;

    protected $fillable = [
        'recipe_id',
        'ingredient_id',
        'quantity_required',
        'unit_of_measure',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:3',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
