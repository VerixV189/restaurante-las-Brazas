<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Suite de Pruebas de Caja Blanca (Criterio de Camino Básico de McCabe)
 * sobre los 3 flujos críticos de Restaurante Las Brazas:
 *  1. HU-13: Registro de pedido móvil, cupones, envío e inventario (Api\OrderController::store & calculate)
 *  2. HU-02: Cierre de comanda en POS y control anti-doble-descuento (PosController::closeOrder & InventoryService::deductForDish)
 *  3. HU-12: Reserva móvil por aforo horario y cancelación controlada (Api\ReservationApiController::store & cancel)
 */
class WhiteBoxCriticalFlowsTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomerUser(string $email = 'cliente@lasbrazas.test', string $name = 'Carlos Cliente'): User
    {
        $role = Role::firstOrCreate(['name' => 'Cliente'], ['description' => 'Comensal']);

        return User::create([
            'role_id' => $role->id,
            'name' => $name,
            'email' => $email,
            'phone' => '70011223',
            'is_active' => true,
            'password' => bcrypt('password123'),
        ]);
    }

    private function createStaffUser(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['description' => "Rol {$roleName}"]);

        return User::create([
            'role_id' => $role->id,
            'name' => "Personal {$roleName}",
            'email' => $email,
            'phone' => '70099887',
            'is_active' => true,
            'password' => bcrypt('password123'),
        ]);
    }

    private function createDishWithOptionalRecipe(float $price = 45.00, bool $available = true, bool $withRecipe = true, float $stock = 10.0, float $minimum = 2.0, float $required = 1.5): array
    {
        $category = Category::firstOrCreate(
            ['slug' => 'cortes-parrilla'],
            ['name' => 'Cortes a la Parrilla', 'is_active' => true]
        );

        $dish = Dish::create([
            'category_id' => $category->id,
            'name' => $withRecipe ? 'Bife Chorizo' : 'Bebida Especial',
            'price' => $price,
            'prep_time_minutes' => 20,
            'is_available' => $available,
        ]);

        $ingredient = null;
        if ($withRecipe) {
            $ingredient = Ingredient::create([
                'name' => 'Carne Res Prime ' . uniqid(),
                'unit_of_measure' => 'kg',
                'current_stock' => $stock,
                'minimum_stock' => $minimum,
                'cost_per_unit' => 50.00,
            ]);

            $recipe = Recipe::create([
                'dish_id' => $dish->id,
                'instructions' => 'Asar a la parrilla.',
            ]);

            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $ingredient->id,
                'quantity_required' => $required,
                'unit_of_measure' => 'kg',
            ]);
        }

        return compact('dish', 'ingredient');
    }

    // =========================================================================
    // FLUJO 1 (HU-13): Api\OrderController::store + calculate — C(G) = 6
    // =========================================================================

    public function test_hu13_c1_rechaza_pedido_sin_autenticacion_o_datos_invalidos(): void
    {
        // Camino C1 (1 -> 2 -> 13): Sin token Sanctum devuelve 401
        $unauthResponse = $this->postJson('/api/v1/orders', []);
        $unauthResponse->assertStatus(401);

        // Con token pero payload inválido (sin items ni order_type) devuelve 422
        Sanctum::actingAs($this->createCustomerUser());
        $invalidResponse = $this->postJson('/api/v1/orders', [
            'order_type' => 'delivery',
            'items' => [],
        ]);
        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_hu13_c2_rechaza_delivery_sin_direccion_de_entrega(): void
    {
        // Camino C2 (1 -> 3 -> 4 -> 13)
        Sanctum::actingAs($this->createCustomerUser());
        ['dish' => $dish] = $this->createDishWithOptionalRecipe(price: 35.00);

        $response = $this->postJson('/api/v1/orders', [
            'order_type' => 'delivery',
            'items' => [
                ['dish_id' => $dish->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Se requiere una dirección de entrega.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_hu13_c3_rechaza_pedido_con_platillo_no_disponible(): void
    {
        // Camino C3 (1 -> 3 -> 5 -> 6 -> 13)
        Sanctum::actingAs($this->createCustomerUser());
        ['dish' => $dish] = $this->createDishWithOptionalRecipe(price: 40.00, available: false);

        $response = $this->postJson('/api/v1/orders', [
            'order_type' => 'takeaway',
            'items' => [
                ['dish_id' => $dish->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('no está disponible', $response->json('message'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_hu13_c4_rechaza_pedido_con_cupon_invalido_o_monto_minimo_no_alcanzado(): void
    {
        // Camino C4 (1 -> 3 -> 5 -> 7 -> 8 -> 13)
        Sanctum::actingAs($this->createCustomerUser());
        ['dish' => $dish] = $this->createDishWithOptionalRecipe(price: 30.00);

        Coupon::create([
            'code' => 'BRASAS20',
            'type' => 'percent',
            'value' => 20,
            'min_order' => 100.00,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'order_type' => 'takeaway',
            'coupon_code' => 'BRASAS20',
            'items' => [
                ['dish_id' => $dish->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Pedido mínimo', $response->json('message'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_hu13_c5_crea_pedido_delivery_con_tarifa_envio_pin_y_descuento_inventario(): void
    {
        // Camino C5 (1 -> 3 -> 5 -> 7 -> 9 -> 10 -> 12 -> 13)
        $user = $this->createCustomerUser();
        Sanctum::actingAs($user);

        $customer = Customer::create([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Casa',
            'street' => 'Av. Cristo Redentor',
            'number' => '450',
            'latitude' => -17.768,
            'longitude' => -63.182,
            'is_default' => true,
        ]);

        ['dish' => $dish, 'ingredient' => $ingredient] = $this->createDishWithOptionalRecipe(
            price: 35.00,
            available: true,
            withRecipe: true,
            stock: 10.0,
            minimum: 2.0,
            required: 1.5
        );

        $response = $this->postJson('/api/v1/orders', [
            'order_type' => 'delivery',
            'address_id' => $address->id,
            'items' => [
                ['dish_id' => $dish->id, 'quantity' => 1, 'notes' => 'Término medio'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
        $this->assertEquals(3.0, (float) $response->json('data.delivery_fee'));

        $order = Order::firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertMatchesRegularExpression('/^\d{4}$/', (string) $order->delivery_pin);
        $this->assertEquals(8.5, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $ingredient->id,
            'movement_type' => 'out',
            'reason' => "Pedido móvil #{$order->id}",
        ]);
    }

    public function test_hu13_c6_crea_pedido_en_mesa_con_cupon_valido_envio_gratis_y_ocupa_mesa(): void
    {
        // Camino C6 (1 -> 3 -> 5 -> 7 -> 9 -> 11 -> 12 -> 13)
        Sanctum::actingAs($this->createCustomerUser('mesa@lasbrazas.test'));

        $area = Area::create(['name' => 'Salón Principal']);
        $table = Table::create([
            'area_id' => $area->id,
            'table_number' => 'M-05',
            'capacity' => 4,
            'status' => 'available',
        ]);

        ['dish' => $dish, 'ingredient' => $ingredient] = $this->createDishWithOptionalRecipe(
            price: 60.00,
            available: true,
            withRecipe: true,
            stock: 12.0,
            minimum: 2.0,
            required: 2.0
        );

        $coupon = Coupon::create([
            'code' => 'VIP10',
            'type' => 'percent',
            'value' => 10,
            'min_order' => 50.00,
            'max_uses' => 5,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'order_type' => 'dine_in',
            'table_id' => $table->id,
            'coupon_code' => 'vip10',
            'items' => [
                ['dish_id' => $dish->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
        $this->assertEquals(0.0, (float) $response->json('data.delivery_fee'));
        $this->assertEquals(6.0, (float) $response->json('data.discount'));

        $this->assertSame('occupied', $table->fresh()->status);
        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertEquals(10.0, (float) $ingredient->fresh()->current_stock);
    }

    // =========================================================================
    // FLUJO 2 (HU-02): PosController::closeOrder + InventoryService — C(G) = 6
    // =========================================================================

    public function test_hu02_c1_bloquea_cierre_por_rol_no_autorizado_o_pago_invalido(): void
    {
        // Camino C1 (1 -> 2 -> 13): Rol Chef no tiene acceso a /pos/orders/{id}/close (403)
        $chef = $this->createStaffUser('Chef', 'chef@lasbrazas.test');
        $order = Order::create([
            'order_type' => 'takeaway',
            'status' => 'ready',
            'subtotal' => 50.00,
            'tax' => 0,
            'total_amount' => 50.00,
        ]);

        $forbidden = $this->actingAs($chef)->post("/pos/orders/{$order->id}/close", [
            'payment_method' => 'cash',
            'amount_paid' => 50.00,
        ]);
        $forbidden->assertStatus(403);

        // Rol Mesero autorizado pero monto inválido (0) falla validación
        $waiter = $this->createStaffUser('Mesero', 'mesero@lasbrazas.test');
        $invalid = $this->actingAs($waiter)->post("/pos/orders/{$order->id}/close", [
            'payment_method' => 'cash',
            'amount_paid' => 0,
        ]);
        $invalid->assertSessionHasErrors(['amount_paid']);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_hu02_c2_cierra_takeaway_ya_entregado_sin_duplicar_descuento_movil(): void
    {
        // Camino C2 (1 -> 3 -> 5 -> 7 -> 8 -> 12 -> 13)
        $waiter = $this->createStaffUser('Mesero', 'mesero2@lasbrazas.test');
        ['dish' => $dish, 'ingredient' => $ingredient] = $this->createDishWithOptionalRecipe(stock: 8.5, required: 1.5);
        $deliveredAt = now()->subMinutes(10);

        $order = Order::create([
            'order_type' => 'takeaway',
            'status' => 'served',
            'subtotal' => 45.00,
            'tax' => 0,
            'total_amount' => 45.00,
            'dispatched_at' => $deliveredAt,
            'delivered_at' => $deliveredAt,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'dish_id' => $dish->id,
            'quantity' => 1,
            'unit_price' => 45.00,
            'subtotal' => 45.00,
            'status' => 'served',
        ]);
        InventoryMovement::create([
            'ingredient_id' => $ingredient->id,
            'movement_type' => 'out',
            'quantity' => 1.5,
            'reason' => "Pedido móvil #{$order->id}",
        ]);

        $response = $this->actingAs($waiter)->post("/pos/orders/{$order->id}/close", [
            'payment_method' => 'cash',
            'amount_paid' => 45.00,
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertEquals(8.5, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_hu02_c3_cierra_delivery_movil_pendiente_de_entrega_y_sella_fechas_sin_doble_descuento(): void
    {
        // Camino C3 (1 -> 3 -> 4 -> 5 -> 7 -> 8 -> 12 -> 13)
        $waiter = $this->createStaffUser('Mesero', 'mesero3@lasbrazas.test');
        ['dish' => $dish, 'ingredient' => $ingredient] = $this->createDishWithOptionalRecipe(stock: 8.5, required: 1.5);

        $order = Order::create([
            'order_type' => 'delivery',
            'status' => 'ready',
            'subtotal' => 45.00,
            'tax' => 0,
            'total_amount' => 48.00,
            'delivery_fee' => 3.00,
            'delivered_at' => null,
            'dispatched_at' => null,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'dish_id' => $dish->id,
            'quantity' => 1,
            'unit_price' => 45.00,
            'subtotal' => 45.00,
            'status' => 'ready',
        ]);
        InventoryMovement::create([
            'ingredient_id' => $ingredient->id,
            'movement_type' => 'out',
            'quantity' => 1.5,
            'reason' => "Pedido móvil #{$order->id}",
        ]);

        $response = $this->actingAs($waiter)->post("/pos/orders/{$order->id}/close", [
            'payment_method' => 'transfer',
            'amount_paid' => 48.00,
            'transaction_ref' => 'QR-99881',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $fresh = $order->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertNotNull($fresh->delivered_at);
        $this->assertNotNull($fresh->dispatched_at);
        $this->assertEquals(8.5, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'transfer',
            'transaction_ref' => 'QR-99881',
        ]);
    }

    public function test_hu02_c4_cierra_comanda_en_mesa_originada_en_movil_libera_mesa_sin_duplicar_stock(): void
    {
        // Camino C4 (1 -> 3 -> 5 -> 6 -> 7 -> 8 -> 12 -> 13)
        $waiter = $this->createStaffUser('Mesero', 'mesero4@lasbrazas.test');
        $area = Area::create(['name' => 'Terraza']);
        $table = Table::create([
            'area_id' => $area->id,
            'table_number' => 'T-02',
            'capacity' => 4,
            'status' => 'occupied',
        ]);
        ['dish' => $dish, 'ingredient' => $ingredient] = $this->createDishWithOptionalRecipe(stock: 7.0, required: 1.5);

        $order = Order::create([
            'table_id' => $table->id,
            'order_type' => 'dine_in',
            'status' => 'served',
            'subtotal' => 45.00,
            'tax' => 0,
            'total_amount' => 45.00,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'dish_id' => $dish->id,
            'quantity' => 1,
            'unit_price' => 45.00,
            'subtotal' => 45.00,
            'status' => 'served',
        ]);
        InventoryMovement::create([
            'ingredient_id' => $ingredient->id,
            'movement_type' => 'out',
            'quantity' => 1.5,
            'reason' => "Pedido móvil #{$order->id}",
        ]);

        $response = $this->actingAs($waiter)->post("/pos/orders/{$order->id}/close", [
            'payment_method' => 'card',
            'amount_paid' => 45.00,
            'transaction_ref' => 'POS-102',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('available', $table->fresh()->status);
        $this->assertEquals(7.0, (float) $ingredient->fresh()->current_stock);
    }

    public function test_hu02_c5_cierra_comanda_pos_de_platillo_sin_receta_y_libera_mesa(): void
    {
        // Camino C5 (1 -> 3 -> 5 -> 6 -> 7 -> 9 -> 10 -> 12 -> 13)
        $admin = $this->createStaffUser('Administrador', 'admin@lasbrazas.test');
        $area = Area::create(['name' => 'VIP']);
        $table = Table::create([
            'area_id' => $area->id,
            'table_number' => 'V-01',
            'capacity' => 6,
            'status' => 'occupied',
        ]);
        ['dish' => $dishWithoutRecipe] = $this->createDishWithOptionalRecipe(price: 20.00, available: true, withRecipe: false);

        $order = Order::create([
            'table_id' => $table->id,
            'user_id' => $admin->id,
            'order_type' => 'dine_in',
            'status' => 'served',
            'subtotal' => 20.00,
            'tax' => 0,
            'total_amount' => 20.00,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'dish_id' => $dishWithoutRecipe->id,
            'quantity' => 1,
            'unit_price' => 20.00,
            'subtotal' => 20.00,
            'status' => 'served',
        ]);

        $response = $this->actingAs($admin)->post("/pos/orders/{$order->id}/close", [
            'payment_method' => 'cash',
            'amount_paid' => 20.00,
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('available', $table->fresh()->status);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_hu02_c6_cierra_comanda_pos_con_receta_descuenta_insumos_y_genera_alerta_stock_bajo(): void
    {
        // Camino C6 (1 -> 3 -> 5 -> 6 -> 7 -> 9 -> 11 -> 12 -> 13)
        Log::spy();

        $waiter = $this->createStaffUser('Mesero', 'mesero6@lasbrazas.test');
        $area = Area::create(['name' => 'Salón']);
        $table = Table::create([
            'area_id' => $area->id,
            'table_number' => 'M-10',
            'capacity' => 4,
            'status' => 'occupied',
        ]);
        ['dish' => $dish, 'ingredient' => $ingredient] = $this->createDishWithOptionalRecipe(
            price: 65.00,
            available: true,
            withRecipe: true,
            stock: 3.0,
            minimum: 2.0,
            required: 1.5
        );

        $order = Order::create([
            'table_id' => $table->id,
            'user_id' => $waiter->id,
            'order_type' => 'dine_in',
            'status' => 'served',
            'subtotal' => 65.00,
            'tax' => 0,
            'total_amount' => 65.00,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'dish_id' => $dish->id,
            'quantity' => 1,
            'unit_price' => 65.00,
            'subtotal' => 65.00,
            'status' => 'served',
        ]);

        $response = $this->actingAs($waiter)->post("/pos/orders/{$order->id}/close", [
            'payment_method' => 'cash',
            'amount_paid' => 65.00,
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('available', $table->fresh()->status);
        $this->assertEquals(1.5, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $ingredient->id,
            'user_id' => $waiter->id,
            'movement_type' => 'out',
        ]);
        Log::shouldHaveReceived('warning')->once();
    }

    // =========================================================================
    // FLUJO 3 (HU-12): ReservationApiController::store + cancel — C(G) = 6
    // =========================================================================

    public function test_hu12_c1_rechaza_reserva_sin_token_o_con_fecha_pasada(): void
    {
        // Camino C1 (1 -> 2 -> 13)
        $this->travelTo(Carbon::parse('2026-11-15 18:00:00'));

        $this->postJson('/api/v1/reservations', [])->assertStatus(401);

        Sanctum::actingAs($this->createCustomerUser('res1@lasbrazas.test'));
        $area = Area::create(['name' => 'Salón']);

        $response = $this->postJson('/api/v1/reservations', [
            'area_id' => $area->id,
            'reservation_date' => '2026-11-15 17:00', // pasada respecto a 18:00
            'adults' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reservation_date']);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_hu12_c2_rechaza_reserva_cuando_no_hay_aforo_disponible_en_la_franja(): void
    {
        // Camino C2 (1 -> 3 -> 4 -> 13)
        $this->travelTo(Carbon::parse('2026-11-15 18:00:00'));

        Sanctum::actingAs($this->createCustomerUser('res2@lasbrazas.test'));
        $area = Area::create(['name' => 'Terraza']);
        Table::create(['area_id' => $area->id, 'table_number' => 'T-01', 'capacity' => 4, 'status' => 'available']);
        Table::create(['area_id' => $area->id, 'table_number' => 'T-02', 'capacity' => 6, 'status' => 'maintenance']);

        $otherCustomer = Customer::create(['name' => 'Otro Comensal', 'phone' => '71111111']);
        Reservation::create([
            'customer_id' => $otherCustomer->id,
            'area_id' => $area->id,
            'reservation_date' => Carbon::parse('2026-11-15 20:30:00'),
            'guest_count' => 3,
            'status' => 'confirmed',
        ]);

        // Queda solo 1 silla operativa en la franja (+/- 2h) y piden para 3 personas
        $response = $this->postJson('/api/v1/reservations', [
            'area_id' => $area->id,
            'reservation_date' => '2026-11-15 21:00',
            'adults' => 2,
            'children' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No hay aforo disponible en esa zona y horario.');
    }

    public function test_hu12_c3_crea_reserva_y_bloquea_cancelacion_de_otro_cliente_con_404(): void
    {
        // Camino C3 (1 -> 3 -> 5 -> 6 -> 7 -> 13)
        $this->travelTo(Carbon::parse('2026-11-15 18:00:00'));

        $owner = $this->createCustomerUser('owner@lasbrazas.test');
        $other = $this->createCustomerUser('intruder@lasbrazas.test');
        $area = Area::create(['name' => 'Salón']);
        Table::create(['area_id' => $area->id, 'table_number' => 'M-01', 'capacity' => 8, 'status' => 'available']);

        Sanctum::actingAs($owner);
        $create = $this->postJson('/api/v1/reservations', [
            'area_id' => $area->id,
            'reservation_date' => '2026-11-15 22:00',
            'adults' => 4,
        ]);
        $create->assertStatus(201);
        $reservationId = $create->json('data.id');

        Sanctum::actingAs($other);
        $cancel = $this->postJson("/api/v1/reservations/{$reservationId}/cancel");
        $cancel->assertStatus(404);
        $this->assertSame('pending', Reservation::findOrFail($reservationId)->status);
    }

    public function test_hu12_c4_rechaza_cancelacion_si_la_reserva_ya_no_esta_pendiente_ni_confirmada(): void
    {
        // Camino C4 (1 -> 3 -> 5 -> 6 -> 8 -> 9 -> 13)
        $this->travelTo(Carbon::parse('2026-11-15 18:00:00'));

        $owner = $this->createCustomerUser('seated@lasbrazas.test');
        $area = Area::create(['name' => 'Salón']);
        Table::create(['area_id' => $area->id, 'table_number' => 'M-02', 'capacity' => 6, 'status' => 'available']);

        Sanctum::actingAs($owner);
        $create = $this->postJson('/api/v1/reservations', [
            'area_id' => $area->id,
            'reservation_date' => '2026-11-15 22:00',
            'adults' => 2,
        ]);
        $create->assertStatus(201);
        $reservationId = $create->json('data.id');

        // El cliente ya se sentó en mesa ('seated')
        Reservation::where('id', $reservationId)->update(['status' => 'seated']);

        $cancel = $this->postJson("/api/v1/reservations/{$reservationId}/cancel");
        $cancel->assertStatus(422)
            ->assertJsonPath('message', 'Esta reserva no se puede cancelar.');
    }

    public function test_hu12_c5_rechaza_cancelacion_con_menos_de_2_horas_de_anticipacion(): void
    {
        // Camino C5 (1 -> 3 -> 5 -> 6 -> 8 -> 10 -> 11 -> 13)
        $this->travelTo(Carbon::parse('2026-11-15 18:00:00'));

        $owner = $this->createCustomerUser('late@lasbrazas.test');
        $area = Area::create(['name' => 'VIP']);
        Table::create(['area_id' => $area->id, 'table_number' => 'V-02', 'capacity' => 6, 'status' => 'available']);

        Sanctum::actingAs($owner);
        $create = $this->postJson('/api/v1/reservations', [
            'area_id' => $area->id,
            'reservation_date' => '2026-11-15 19:30', // A solo 1h30 de distancia (< 2h)
            'adults' => 3,
        ]);
        $create->assertStatus(201);
        $reservationId = $create->json('data.id');

        $cancel = $this->postJson("/api/v1/reservations/{$reservationId}/cancel");
        $cancel->assertStatus(422)
            ->assertJsonPath('message', 'Solo se puede cancelar con al menos 2 horas de anticipación.');
        $this->assertSame('pending', Reservation::findOrFail($reservationId)->status);
    }

    public function test_hu12_c6_crea_reserva_con_tolerancia_y_permite_cancelacion_anticipada(): void
    {
        // Camino C6 (1 -> 3 -> 5 -> 6 -> 8 -> 10 -> 12 -> 13)
        $this->travelTo(Carbon::parse('2026-11-15 18:00:00'));

        $owner = $this->createCustomerUser('ok@lasbrazas.test');
        $area = Area::create(['name' => 'Terraza']);
        Table::create(['area_id' => $area->id, 'table_number' => 'T-04', 'capacity' => 8, 'status' => 'available']);

        Sanctum::actingAs($owner);
        $create = $this->postJson('/api/v1/reservations', [
            'area_id' => $area->id,
            'reservation_date' => '2026-11-15 22:30', // 4h30 de anticipación (> 2h)
            'adults' => 2,
            'children' => 1,
            'special_requests' => 'Silla para bebé',
        ]);
        $create->assertStatus(201)
            ->assertJsonPath('success', true);
        $this->assertStringContainsString('15 minutos de tolerancia', $create->json('message'));

        $reservationId = $create->json('data.id');
        $cancel = $this->postJson("/api/v1/reservations/{$reservationId}/cancel");

        $cancel->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Reserva cancelada.');
        $this->assertSame('cancelled', Reservation::findOrFail($reservationId)->status);
    }
}
