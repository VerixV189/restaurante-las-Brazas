<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Supplier extends Model
{
    use LogsActivity;

    protected $fillable = ['company_name', 'contact_name', 'phone', 'email', 'address'];

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }
}
