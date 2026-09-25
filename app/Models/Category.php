<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Category extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'slug', 'description', 'icon', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
}
