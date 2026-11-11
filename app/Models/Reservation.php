<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Reservation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'customer_id',
        'table_id',
        'user_id',
        'reservation_date',
        'guest_count',
        'status',
        'special_requests',
        'area_id',
        'children_count',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
