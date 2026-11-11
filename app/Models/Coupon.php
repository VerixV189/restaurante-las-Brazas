<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'min_order', 'max_uses', 'used_count', 'expires_at', 'is_active'];

    protected $casts = ['expires_at' => 'datetime', 'is_active' => 'boolean'];

    /** Retorna mensaje de error o null si el cupón es válido para el subtotal dado. */
    public function validationError(float $subtotal): ?string
    {
        if (! $this->is_active) return 'El cupón no está activo.';
        if ($this->expires_at && $this->expires_at->isPast()) return 'El cupón ha expirado.';
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) return 'El cupón alcanzó su límite de usos.';
        if ($subtotal < (float) $this->min_order) return "Pedido mínimo para este cupón: {$this->min_order}.";
        return null;
    }

    /** Calcula [descuento, envío_gratis]. */
    public function apply(float $subtotal): array
    {
        return match ($this->type) {
            'percent' => [round($subtotal * min((float) $this->value, 100) / 100, 2), false],
            'fixed' => [min((float) $this->value, $subtotal), false],
            default => [0.0, true],
        };
    }
}

