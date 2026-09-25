<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Area extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'description'];

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }
}
