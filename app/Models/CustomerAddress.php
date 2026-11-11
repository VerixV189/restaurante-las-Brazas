<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id', 'label', 'street', 'number', 'floor',
        'reference', 'latitude', 'longitude', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function fullText(): string
    {
        return trim("{$this->street} {$this->number}"
            . ($this->floor ? ", Piso/Dpto {$this->floor}" : '')
            . ($this->reference ? " (Ref: {$this->reference})" : ''));
    }
}

