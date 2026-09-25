<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Area;
use App\Models\Table;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Ingredient;
use App\Models\Dish;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $adminRole = Role::firstOrCreate(['name' => 'Administrador'], ['description' => 'Acceso total al sistema']);
        $chefRole = Role::firstOrCreate(['name' => 'Chef'], ['description' => 'Gestión de comandas e inventario']);
        $waiterRole = Role::firstOrCreate(['name' => 'Mesero'], ['description' => 'Toma de pedidos y atención de mesas']);
        $receptionRole = Role::firstOrCreate(['name' => 'Recepcionista'], ['description' => 'Gestión del mapa de mesas y reservas']);

        // 2. Admin User
        User::firstOrCreate(
            ['email' => 'admin@lasbrazas.com'],
            [
                'name' => 'Administrador Las Brazas',
                'role_id' => $adminRole->id,
                'password' => Hash::make('password123'),
                'phone' => '555-0199',
                'is_active' => true,
            ]
        );

        // 3. Áreas y Mesas
        $terraza = Area::firstOrCreate(['name' => 'Terraza Principal'], ['description' => 'Área al aire libre']);
        $salon = Area::firstOrCreate(['name' => 'Salón General'], ['description' => 'Área climatizada interior']);

        Table::firstOrCreate(['table_number' => 'Mesa 1'], ['area_id' => $terraza->id, 'capacity' => 4, 'status' => 'available']);
        Table::firstOrCreate(['table_number' => 'Mesa 2'], ['area_id' => $terraza->id, 'capacity' => 2, 'status' => 'available']);
        Table::firstOrCreate(['table_number' => 'Mesa 3'], ['area_id' => $salon->id, 'capacity' => 6, 'status' => 'available']);
        Table::firstOrCreate(['table_number' => 'Mesa VIP 1'], ['area_id' => $salon->id, 'capacity' => 8, 'status' => 'available']);

        // 4. Categorías de Menú
        $entradas = Category::firstOrCreate(['slug' => 'entradas'], ['name' => 'Entradas', 'description' => 'Botanas y aperitivos', 'icon' => 'Utensils']);
        $cortes = Category::firstOrCreate(['slug' => 'cortes-y-parrilladas'], ['name' => 'Cortes & Parrilladas', 'description' => 'Especialidades a la leña y carbón', 'icon' => 'Flame']);
        $bebidas = Category::firstOrCreate(['slug' => 'bebidas'], ['name' => 'Bebidas', 'description' => 'Refrescos, aguas y coctelería', 'icon' => 'GlassWater']);

        // 5. Proveedor e Ingredientes iniciales
        $proveedor = Supplier::firstOrCreate(
            ['company_name' => 'Carnes & Insumos del Norte'],
            ['contact_name' => 'Roberto Gómez', 'phone' => '555-4321', 'email' => 'ventas@carnesnorte.com']
        );

        Ingredient::firstOrCreate(
            ['name' => 'Carne Arrachera Prime'],
            ['supplier_id' => $proveedor->id, 'unit_of_measure' => 'kg', 'current_stock' => 25.500, 'minimum_stock' => 5.000, 'cost_per_unit' => 220.00]
        );

        Ingredient::firstOrCreate(
            ['name' => 'Queso Gouda para Fundir'],
            ['supplier_id' => $proveedor->id, 'unit_of_measure' => 'kg', 'current_stock' => 10.000, 'minimum_stock' => 2.000, 'cost_per_unit' => 140.00]
        );

        // 6. Platillos de muestra
        $arracheraDish = Dish::firstOrCreate(
            ['name' => 'Arrachera Las Brazas (300g)'],
            [
                'category_id' => $cortes->id,
                'description' => 'Acompañada de cebollitas cambray, chiles toreados y guacamole.',
                'price' => 320.00,
                'prep_time_minutes' => 20,
                'is_available' => true
            ]
        );

        $quesoDish = Dish::firstOrCreate(
            ['name' => 'Queso Fundido con Chistorra'],
            [
                'category_id' => $entradas->id,
                'description' => 'Servido en cazuela de barro caliente con tortillas de harina.',
                'price' => 145.00,
                'prep_time_minutes' => 10,
                'is_available' => true
            ]
        );

        // 7. Fichas Técnicas / Recetas
        $arracheraIng = Ingredient::where('name', 'Carne Arrachera Prime')->first();
        $quesoIng = Ingredient::where('name', 'Queso Gouda para Fundir')->first();

        if ($arracheraDish && $arracheraIng) {
            $recipe1 = \App\Models\Recipe::firstOrCreate(
                ['dish_id' => $arracheraDish->id],
                ['instructions' => "1. Marinar el corte con sal de grano y pimienta negra.\n2. Sellar a fuego directo 4 min por lado a término medio.\n3. Servir con chiles toreados."]
            );

            \App\Models\RecipeIngredient::firstOrCreate(
                ['recipe_id' => $recipe1->id, 'ingredient_id' => $arracheraIng->id],
                ['quantity_required' => 0.300, 'unit_of_measure' => 'kg']
            );
        }

        if ($quesoDish && $quesoIng) {
            $recipe2 = \App\Models\Recipe::firstOrCreate(
                ['dish_id' => $quesoDish->id],
                ['instructions' => "1. Calentar cazuela de barro en horno de leña.\n2. Fundir queso gouda con chistorra dorada.\n3. Servir inmediatamente con tortillas."]
            );

            \App\Models\RecipeIngredient::firstOrCreate(
                ['recipe_id' => $recipe2->id, 'ingredient_id' => $quesoIng->id],
                ['quantity_required' => 0.200, 'unit_of_measure' => 'kg']
            );
        }

        // 8. Clientes Frecuentes de muestra
        \App\Models\Customer::firstOrCreate(
            ['email' => 'carlos.mendoza@empresa.com'],
            [
                'name' => 'Lic. Carlos Mendoza',
                'phone' => '555-8833',
                'preferences_notes' => 'Cliente VIP. Prefiere mesa en Terraza. Término de la carne 3/4.'
            ]
        );

        \App\Models\Customer::firstOrCreate(
            ['email' => 'mariana.silva@gmail.com'],
            [
                'name' => 'Dra. Mariana Silva',
                'phone' => '555-1928',
                'preferences_notes' => 'Alergia a los mariscos y frutos secos.'
            ]
        );

        // 9. Movimientos iniciales de Inventario
        $admin = User::first();
        if ($arracheraIng && $admin) {
            \App\Models\InventoryMovement::firstOrCreate(
                ['reason' => 'Compra inicial de lote semanal'],
                [
                    'ingredient_id' => $arracheraIng->id,
                    'user_id' => $admin->id,
                    'movement_type' => 'in',
                    'quantity' => 30.000,
                ]
            );

            \App\Models\InventoryMovement::firstOrCreate(
                ['reason' => 'Consumo por preparación de 15x Arrachera Las Brazas'],
                [
                    'ingredient_id' => $arracheraIng->id,
                    'user_id' => $admin->id,
                    'movement_type' => 'out',
                    'quantity' => 4.500,
                ]
            );

            \App\Models\InventoryMovement::firstOrCreate(
                ['reason' => 'Merma por corte de grasa no aprovechable'],
                [
                    'ingredient_id' => $arracheraIng->id,
                    'user_id' => $admin->id,
                    'movement_type' => 'waste',
                    'quantity' => 0.650,
                ]
            );
        }

        // 10. Comanda / Orden activa de muestra en Mesa 1
        $table1 = Table::where('table_number', 'Mesa 1')->first();
        $dish1 = Dish::where('name', 'Arrachera Las Brazas (300g)')->first();
        $dish2 = Dish::where('name', 'Queso Fundido con Chistorra')->first();
        $customer1 = \App\Models\Customer::first();

        if ($table1 && $dish1 && $dish2 && $admin) {
            $table1->update(['status' => 'occupied']);

            $order = \App\Models\Order::firstOrCreate(
                ['table_id' => $table1->id, 'status' => 'in_kitchen'],
                [
                    'user_id' => $admin->id,
                    'customer_id' => $customer1?->id,
                    'order_type' => 'dine_in',
                    'subtotal' => 465.00,
                    'tax' => 0,
                    'total_amount' => 465.00,
                ]
            );

            \App\Models\OrderItem::firstOrCreate(
                ['order_id' => $order->id, 'dish_id' => $dish1->id],
                [
                    'quantity' => 1,
                    'unit_price' => $dish1->price,
                    'subtotal' => $dish1->price,
                    'notes' => 'Término 3/4, chiles bien toreados',
                    'status' => 'cooking',
                ]
            );

            \App\Models\OrderItem::firstOrCreate(
                ['order_id' => $order->id, 'dish_id' => $dish2->id],
                [
                    'quantity' => 1,
                    'unit_price' => $dish2->price,
                    'subtotal' => $dish2->price,
                    'notes' => 'Tortillas de harina extras',
                    'status' => 'ready',
                ]
            );
        }

        // 11. Órdenes pagadas históricas para reportes y analíticas
        $pastDays = [
            ['daysAgo' => 4, 'total' => 1280.00, 'qty' => 4],
            ['daysAgo' => 3, 'total' => 2450.00, 'qty' => 7],
            ['daysAgo' => 2, 'total' => 1890.00, 'qty' => 5],
            ['daysAgo' => 1, 'total' => 3100.00, 'qty' => 9],
            ['daysAgo' => 0, 'total' => 1420.00, 'qty' => 4],
        ];

        if ($dish1 && $admin) {
            foreach ($pastDays as $idx => $day) {
                $pastDate = now()->subDays($day['daysAgo'])->setHour(14)->setMinute(30);

                $pastOrder = \App\Models\Order::firstOrCreate(
                    [
                        'user_id' => $admin->id,
                        'status' => 'paid',
                        'subtotal' => $day['total'],
                        'total_amount' => $day['total'],
                        'created_at' => $pastDate,
                    ],
                    [
                        'order_type' => 'dine_in',
                        'tax' => 0,
                        'updated_at' => $pastDate,
                    ]
                );

                \App\Models\OrderItem::firstOrCreate(
                    ['order_id' => $pastOrder->id, 'dish_id' => $dish1->id],
                    [
                        'quantity' => $day['qty'],
                        'unit_price' => $dish1->price,
                        'subtotal' => $day['total'],
                        'notes' => 'Comanda histórica',
                        'status' => 'served',
                        'created_at' => $pastDate,
                    ]
                );

                \App\Models\Payment::firstOrCreate(
                    ['order_id' => $pastOrder->id],
                    [
                        'payment_method' => $idx % 2 === 0 ? 'card' : 'cash',
                        'amount' => $day['total'],
                        'transaction_ref' => "SEED-PAY-{$idx}",
                        'paid_at' => $pastDate,
                        'created_at' => $pastDate,
                    ]
                );
            }
        }
    }
}
