<?php

namespace Database\Seeders;

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
use App\Models\Payment;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a rich, realistic steakhouse dataset.
     */
    public function run(): void
    {
        // =====================================================================
        // 1. ROLES DEL SISTEMA
        // =====================================================================
        $rolesData = [
            'Administrador' => 'Acceso total al sistema, reportes ejecutivos y configuración',
            'Chef'          => 'Gestión de comandas KDS, recetas, insumos y control de mermas',
            'Mesero'        => 'Toma de pedidos en salón, POS y atención de mesas',
            'Recepcionista' => 'Gestión del mapa de mesas, planos IA y reservaciones',
            'Repartidor'    => 'Logística y entrega de pedidos a domicilio desde la App Móvil',
            'Cliente'       => 'Comensal registrado en la aplicación móvil Flutter',
        ];

        $roles = [];
        foreach ($rolesData as $name => $description) {
            $roles[$name] = Role::updateOrCreate(['name' => $name], ['description' => $description]);
        }

        // =====================================================================
        // 2. USUARIOS (PERSONAL Y CLIENTES APP) - Contraseña: password123
        // =====================================================================
        $usersData = [
            ['name' => 'Administrador Las Brazas',       'email' => 'admin@lasbrazas.com',          'phone' => '700-10001', 'role' => 'Administrador'],
            ['name' => 'Ing. Rodrigo Valenzuela',        'email' => 'gerente@lasbrazas.com',        'phone' => '700-10002', 'role' => 'Administrador'],
            ['name' => 'Chef Ejecutivo Martín Herrera',  'email' => 'chef@lasbrazas.com',           'phone' => '700-20001', 'role' => 'Chef'],
            ['name' => 'Maestro Parrillero Diego Morales','email' => 'parrillero@lasbrazas.com',    'phone' => '700-20002', 'role' => 'Chef'],
            ['name' => 'Alejandro Torres (Mesero)',      'email' => 'mesero1@lasbrazas.com',        'phone' => '700-30001', 'role' => 'Mesero'],
            ['name' => 'Valeria Rojas (Mesera)',         'email' => 'mesero2@lasbrazas.com',        'phone' => '700-30002', 'role' => 'Mesero'],
            ['name' => 'Sebastián Guzmán (Mesero)',      'email' => 'mesero3@lasbrazas.com',        'phone' => '700-30003', 'role' => 'Mesero'],
            ['name' => 'Camila Vargas (Hostess)',        'email' => 'recepcion@lasbrazas.com',      'phone' => '700-40001', 'role' => 'Recepcionista'],
            ['name' => 'Fernando Méndez (Delivery)',     'email' => 'repartidor1@lasbrazas.com',    'phone' => '700-50001', 'role' => 'Repartidor'],
            ['name' => 'Luis Alberto Ortiz (Delivery)',  'email' => 'repartidor2@lasbrazas.com',    'phone' => '700-50002', 'role' => 'Repartidor'],
            ['name' => 'Lic. Carlos Mendoza',            'email' => 'carlos.mendoza@empresa.com',   'phone' => '710-88331', 'role' => 'Cliente'],
            ['name' => 'Dra. Mariana Silva',             'email' => 'mariana.silva@gmail.com',      'phone' => '710-19282', 'role' => 'Cliente'],
            ['name' => 'Arq. Roberto Justiniano',        'email' => 'roberto.j@arquitectura.bo',    'phone' => '710-44510', 'role' => 'Cliente'],
            ['name' => 'Lic. Sofía Landívar',            'email' => 'sofia.landivar@finanzas.com',  'phone' => '710-99201', 'role' => 'Cliente'],
        ];

        $users = [];
        $defaultPassword = Hash::make('password123');
        foreach ($usersData as $u) {
            $users[$u['email']] = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name'      => $u['name'],
                    'role_id'   => $roles[$u['role']]->id,
                    'password'  => $defaultPassword,
                    'phone'     => $u['phone'],
                    'is_active' => true,
                ]
            );
        }

        $adminUser = $users['admin@lasbrazas.com'];
        $waiter1   = $users['mesero1@lasbrazas.com'];
        $waiter2   = $users['mesero2@lasbrazas.com'];
        $driver1   = $users['repartidor1@lasbrazas.com'];

        // =====================================================================
        // 3. ZONAS / ÁREAS Y MESAS DEL RESTAURANTE (16 MESAS EN 4 ZONAS)
        // =====================================================================
        $areasData = [
            'Terraza Principal'   => 'Área al aire libre con jardín vertical y vista al grill exterior',
            'Salón General'       => 'Ambiente climatizado interior con música instrumental y vista a cocina',
            'Salón VIP & Cava'    => 'Salón privado insonorizado rodeado de nuestra cava de vinos de guarda',
            'Barra & Lounge'      => 'Zona casual frente a la barra de coctelería de autor y pantallas HD',
        ];

        $areas = [];
        foreach ($areasData as $name => $desc) {
            $areas[$name] = Area::updateOrCreate(['name' => $name], ['description' => $desc]);
        }

        $tablesData = [
            // Terraza Principal
            ['number' => 'Mesa 1',     'area' => 'Terraza Principal', 'capacity' => 4,  'status' => 'occupied'],
            ['number' => 'Mesa 2',     'area' => 'Terraza Principal', 'capacity' => 6,  'status' => 'available'],
            ['number' => 'Mesa 4',     'area' => 'Terraza Principal', 'capacity' => 4,  'status' => 'reserved'],
            ['number' => 'Mesa 5',     'area' => 'Terraza Principal', 'capacity' => 2,  'status' => 'available'],
            ['number' => 'Mesa 6',     'area' => 'Terraza Principal', 'capacity' => 8,  'status' => 'available'],
            // Salón General
            ['number' => 'Mesa 3',     'area' => 'Salón General',     'capacity' => 6,  'status' => 'occupied'],
            ['number' => 'Mesa 7',     'area' => 'Salón General',     'capacity' => 4,  'status' => 'available'],
            ['number' => 'Mesa 8',     'area' => 'Salón General',     'capacity' => 4,  'status' => 'available'],
            ['number' => 'Mesa 9',     'area' => 'Salón General',     'capacity' => 2,  'status' => 'available'],
            ['number' => 'Mesa 10',    'area' => 'Salón General',     'capacity' => 6,  'status' => 'reserved'],
            // Salón VIP & Cava
            ['number' => 'Mesa VIP 1', 'area' => 'Salón VIP & Cava',  'capacity' => 8,  'status' => 'occupied'],
            ['number' => 'Mesa VIP 2', 'area' => 'Salón VIP & Cava',  'capacity' => 10, 'status' => 'available'],
            ['number' => 'Mesa VIP 3', 'area' => 'Salón VIP & Cava',  'capacity' => 12, 'status' => 'reserved'],
            // Barra & Lounge
            ['number' => 'Barra 1',    'area' => 'Barra & Lounge',    'capacity' => 2,  'status' => 'available'],
            ['number' => 'Barra 2',    'area' => 'Barra & Lounge',    'capacity' => 2,  'status' => 'available'],
            ['number' => 'Lounge 1',   'area' => 'Barra & Lounge',    'capacity' => 4,  'status' => 'available'],
        ];

        $tables = [];
        foreach ($tablesData as $t) {
            $tables[$t['number']] = Table::updateOrCreate(
                ['table_number' => $t['number']],
                [
                    'area_id'  => $areas[$t['area']]->id,
                    'capacity' => $t['capacity'],
                    'status'   => $t['status'],
                ]
            );
        }

        // =====================================================================
        // 4. CATEGORÍAS DEL MENÚ (8 CATEGORÍAS)
        // =====================================================================
        $categoriesData = [
            ['slug' => 'entradas',                    'name' => 'Entradas',                    'description' => 'Botanas al carbón, quesos fundidos y embutidos artesanales',   'icon' => 'Utensils'],
            ['slug' => 'cortes-y-parrilladas',        'name' => 'Cortes & Parrilladas',        'description' => 'Cortes finos madurados a la leña y carbón de quebracho',       'icon' => 'Flame'],
            ['slug' => 'parrilladas-para-compartir',  'name' => 'Parrilladas para Compartir',  'description' => 'Tablas familiares y banquetes de brasas al centro',            'icon' => 'Users'],
            ['slug' => 'hamburguesas-y-sandwiches',   'name' => 'Hamburguesas & Sándwiches',   'description' => 'Burgers gourmet de Wagyu/Sirloin ahumadas y choripanes',       'icon' => 'Beef'],
            ['slug' => 'guarniciones-y-ensaladas',    'name' => 'Guarniciones & Ensaladas',    'description' => 'Acompañamientos frescos de huerto y vegetales al rescoldo',    'icon' => 'Salad'],
            ['slug' => 'postres-artesanales',         'name' => 'Postres Artesanales',         'description' => 'Dulces de autor con chocolate, dulce de leche y flambé',       'icon' => 'Cake'],
            ['slug' => 'bebidas',                     'name' => 'Bebidas',                     'description' => 'Refrescos, limonadas de autor y aguas naturales',              'icon' => 'GlassWater'],
            ['slug' => 'cocteleria-y-vinos',          'name' => 'Coctelería & Vinos',          'description' => 'Vinos tintos de guarda, cervezas artesanales y mixología',     'icon' => 'Wine'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::updateOrCreate(
                ['slug' => $c['slug']],
                [
                    'name'        => $c['name'],
                    'description' => $c['description'],
                    'icon'        => $c['icon'],
                    'is_active'   => true,
                ]
            );
        }

        // =====================================================================
        // 5. PROVEEDORES (6 PROVEEDORES)
        // =====================================================================
        $suppliersData = [
            ['company_name' => 'Carnes & Insumos del Norte',      'contact_name' => 'Roberto Gómez',     'phone' => '3-3459810', 'email' => 'ventas@carnesnorte.com',       'address' => 'Parque Industrial Mz. 4, Nave 12'],
            ['company_name' => 'Frigorífico Santa Cruz Prime',    'contact_name' => 'Ing. Gustavo Paz',  'phone' => '3-3882100', 'email' => 'pedidos@santacruzprime.bo',    'address' => 'Av. Cristo Redentor Km 8.5'],
            ['company_name' => 'Lácteos & Quesos del Valle',      'contact_name' => 'Lucía Castellanos', 'phone' => '4-4221980', 'email' => 'comercial@quesosdelvalle.com', 'address' => 'Av. Blanco Galindo Km 5'],
            ['company_name' => 'Huerto Orgánico Los Andes',       'contact_name' => 'Mario Quispe',      'phone' => '715-88210', 'email' => 'contacto@huertoandes.bo',      'address' => 'Mercado Mayorista Abasto Bloque C'],
            ['company_name' => 'Bodega & Viñedos del Sur',        'contact_name' => 'Sommelier Jean-Luc','phone' => '4-4661029', 'email' => 'reservas@vinedosdelsur.com',   'address' => 'Av. Las Américas #820'],
            ['company_name' => 'Maderas & Carbón Quebracho Real', 'contact_name' => 'Héctor Villarroel', 'phone' => '720-99104', 'email' => 'logistica@quebrachoreal.com',  'address' => 'Carretera al Chaco Km 14'],
        ];

        $suppliers = [];
        foreach ($suppliersData as $s) {
            $suppliers[$s['company_name']] = Supplier::updateOrCreate(
                ['company_name' => $s['company_name']],
                $s
            );
        }

        // =====================================================================
        // 6. INGREDIENTES / INSUMOS (26 INSUMOS, ALGUNOS CON STOCK CRÍTICO)
        // =====================================================================
        $ingredientsData = [
            // Carnes y Embutidos
            ['name' => 'Carne Arrachera Prime',          'supplier' => 'Carnes & Insumos del Norte',      'unit' => 'kg',  'stock' => 28.500, 'min' => 8.000,  'cost' => 210.00],
            ['name' => 'Bife de Chorizo Angus',          'supplier' => 'Frigorífico Santa Cruz Prime',    'unit' => 'kg',  'stock' => 22.000, 'min' => 6.000,  'cost' => 245.00],
            ['name' => 'Picaña Importada Seleccionada',  'supplier' => 'Frigorífico Santa Cruz Prime',    'unit' => 'kg',  'stock' => 19.400, 'min' => 6.000,  'cost' => 230.00],
            ['name' => 'Ojo de Bife (Ribeye Prime)',     'supplier' => 'Frigorífico Santa Cruz Prime',    'unit' => 'kg',  'stock' => 14.200, 'min' => 5.000,  'cost' => 275.00],
            ['name' => 'Tomahawk Steak con Hueso',       'supplier' => 'Frigorífico Santa Cruz Prime',    'unit' => 'kg',  'stock' => 4.500,  'min' => 6.000,  'cost' => 310.00], // Stock Crítico!
            ['name' => 'Asado de Tira / Short Rib',      'supplier' => 'Carnes & Insumos del Norte',      'unit' => 'kg',  'stock' => 16.800, 'min' => 5.000,  'cost' => 185.00],
            ['name' => 'Costillar de Cerdo St. Louis',   'supplier' => 'Carnes & Insumos del Norte',      'unit' => 'kg',  'stock' => 15.000, 'min' => 4.000,  'cost' => 145.00],
            ['name' => 'Chorizo Argentino Artesanal',    'supplier' => 'Carnes & Insumos del Norte',      'unit' => 'kg',  'stock' => 18.000, 'min' => 5.000,  'cost' => 98.00],
            ['name' => 'Chistorra Navarra Ahumada',      'supplier' => 'Carnes & Insumos del Norte',      'unit' => 'kg',  'stock' => 11.500, 'min' => 3.000,  'cost' => 125.00],
            ['name' => 'Tuétano de Res Seleccionado',    'supplier' => 'Carnes & Insumos del Norte',      'unit' => 'kg',  'stock' => 8.000,  'min' => 2.500,  'cost' => 90.00],
            ['name' => 'Blend Hamburguesa Wagyu/Sirloin','supplier' => 'Frigorífico Santa Cruz Prime',    'unit' => 'kg',  'stock' => 12.600, 'min' => 4.000,  'cost' => 175.00],
            // Lácteos
            ['name' => 'Queso Gouda para Fundir',        'supplier' => 'Lácteos & Quesos del Valle',      'unit' => 'kg',  'stock' => 14.000, 'min' => 4.000,  'cost' => 135.00],
            ['name' => 'Queso Provolone Ahumado',        'supplier' => 'Lácteos & Quesos del Valle',      'unit' => 'kg',  'stock' => 9.500,  'min' => 3.000,  'cost' => 160.00],
            ['name' => 'Dulce de Leche Repostero',       'supplier' => 'Lácteos & Quesos del Valle',      'unit' => 'kg',  'stock' => 6.200,  'min' => 2.000,  'cost' => 68.00],
            // Vegetales y Huerto
            ['name' => 'Papa Harinosa Seleccionada',     'supplier' => 'Huerto Orgánico Los Andes',       'unit' => 'kg',  'stock' => 45.000, 'min' => 15.000, 'cost' => 14.00],
            ['name' => 'Yuca Fresca Pelada',             'supplier' => 'Huerto Orgánico Los Andes',       'unit' => 'kg',  'stock' => 28.000, 'min' => 10.000, 'cost' => 12.00],
            ['name' => 'Aguacate Hass de Primera',       'supplier' => 'Huerto Orgánico Los Andes',       'unit' => 'kg',  'stock' => 3.200,  'min' => 5.000,  'cost' => 48.00], // Stock Crítico!
            ['name' => 'Espárragos Verdes Frescos',      'supplier' => 'Huerto Orgánico Los Andes',       'unit' => 'kg',  'stock' => 1.800,  'min' => 3.000,  'cost' => 85.00], // Stock Crítico!
            ['name' => 'Limón Real & Hierbabuena',       'supplier' => 'Huerto Orgánico Los Andes',       'unit' => 'kg',  'stock' => 14.500, 'min' => 4.000,  'cost' => 18.00],
            // Panadería y Abarrotes
            ['name' => 'Pan Brioche Artesanal',          'supplier' => 'Lácteos & Quesos del Valle',      'unit' => 'pza', 'stock' => 48.000, 'min' => 15.000, 'cost' => 6.50],
            ['name' => 'Tortillas de Harina Caseras',    'supplier' => 'Lácteos & Quesos del Valle',      'unit' => 'paq', 'stock' => 24.000, 'min' => 8.000,  'cost' => 18.00],
            ['name' => 'Chocolate Semiamargo 70%',       'supplier' => 'Lácteos & Quesos del Valle',      'unit' => 'kg',  'stock' => 1.500,  'min' => 2.500,  'cost' => 140.00], // Stock Crítico!
            // Bebidas y Vinos
            ['name' => 'Vino Malbec Reserva Mendoza',    'supplier' => 'Bodega & Viñedos del Sur',        'unit' => 'bot', 'stock' => 5.000,  'min' => 6.000,  'cost' => 165.00], // Stock Crítico!
            ['name' => 'Vino Cabernet Sauvignon Gran',   'supplier' => 'Bodega & Viñedos del Sur',        'unit' => 'bot', 'stock' => 18.000, 'min' => 6.000,  'cost' => 180.00],
            ['name' => 'Cerveza Artesanal IPA / Roja',   'supplier' => 'Bodega & Viñedos del Sur',        'unit' => 'bot', 'stock' => 64.000, 'min' => 24.000, 'cost' => 26.00],
            // Insumos de Fuego
            ['name' => 'Carbón de Quebracho Blanco',     'supplier' => 'Maderas & Carbón Quebracho Real', 'unit' => 'kg',  'stock' => 120.000,'min' => 40.000, 'cost' => 9.50],
        ];

        $ingredients = [];
        foreach ($ingredientsData as $ing) {
            $ingredients[$ing['name']] = Ingredient::updateOrCreate(
                ['name' => $ing['name']],
                [
                    'supplier_id'     => $suppliers[$ing['supplier']]->id,
                    'unit_of_measure' => $ing['unit'],
                    'current_stock'   => $ing['stock'],
                    'minimum_stock'   => $ing['min'],
                    'cost_per_unit'   => $ing['cost'],
                ]
            );
        }

        // =====================================================================
        // 7. PLATILLOS DEL MENÚ (30 PLATILLOS) + FICHAS TÉCNICAS (RECETAS)
        // =====================================================================
        $dishesData = [
            // ── ENTRADAS (5) ──
            [
                'name' => 'Queso Fundido con Chistorra',
                'cat'  => 'entradas',
                'price'=> 145.00,
                'prep' => 10,
                'img'  => 'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Servido en cazuela de barro hirviendo con queso gouda seleccionado, chistorra navarra dorada y tortillas de harina.',
                'inst' => "1. Precalentar cazuela de barro a las brasas.\n2. Dorar chistorra en rodajas.\n3. Fundir queso gouda y coronar con chistorra y tortillas calientes.",
                'recipe' => [
                    ['ing' => 'Queso Gouda para Fundir', 'qty' => 0.220, 'unit' => 'kg'],
                    ['ing' => 'Chistorra Navarra Ahumada', 'qty' => 0.120, 'unit' => 'kg'],
                    ['ing' => 'Tortillas de Harina Caseras', 'qty' => 0.500, 'unit' => 'paq'],
                ],
            ],
            [
                'name' => 'Provoleta Ahumada al Orégano',
                'cat'  => 'entradas',
                'price'=> 160.00,
                'prep' => 12,
                'img'  => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Disco de queso provolone estacionado asado a la parrilla con costra crocante, tomates cherry confitados y chimichurri.',
                'inst' => "1. Sellar el disco de provolone en plancha de hierro bien caliente.\n2. Condimentar con orégano andino, ají molido y aceite de oliva.",
                'recipe' => [
                    ['ing' => 'Queso Provolone Ahumado', 'qty' => 0.250, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Chorizo Argentino a la Parrilla (Par)',
                'cat'  => 'entradas',
                'price'=> 130.00,
                'prep' => 15,
                'img'  => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Dos chorizos artesanales de puro cerdo y res asados lentamente al quebracho, acompañados de salsa criolla y pan de campo.',
                'inst' => "1. Asar a fuego medio durante 12 minutos volteando una vez.\n2. Servir con salsa criolla fresca y chimichurri.",
                'recipe' => [
                    ['ing' => 'Chorizo Argentino Artesanal', 'qty' => 0.300, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Tuétanos Rostizados al Carbón',
                'cat'  => 'entradas',
                'price'=> 210.00,
                'prep' => 18,
                'img'  => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Canoas de tuétano de res asadas a fuego directo con costra de ajo rostizado, perejil fresco y tostadas de masa madre.',
                'inst' => "1. Colocar las canoas de hueso sobre la parrilla fuerte por 15 min.\n2. Terminar con sal de grano ahumada y perejil picado.",
                'recipe' => [
                    ['ing' => 'Tuétano de Res Seleccionado', 'qty' => 0.450, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Empanadas Cortadas a Cuchillo (3 pzas)',
                'cat'  => 'entradas',
                'price'=> 115.00,
                'prep' => 12,
                'img'  => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Empanadas horneadas al barro rellenas de carne de bife cortada a cuchillo, cebolla tierna, comino y llajua casera.',
                'inst' => "1. Hornear a 260°C en horno de leña por 10 minutos hasta dorar.\n2. Acompañar con gajos de limón y llajua.",
                'recipe' => [
                    ['ing' => 'Carne Arrachera Prime', 'qty' => 0.180, 'unit' => 'kg'],
                ],
            ],

            // ── CORTES & PARRILLADAS (7) ──
            [
                'name' => 'Arrachera Las Brazas (300g)',
                'cat'  => 'cortes-y-parrilladas',
                'price'=> 320.00,
                'prep' => 20,
                'img'  => 'https://images.unsplash.com/photo-1558030006-450675393462?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Corte insignia marinado por 24 horas, acompañado de cebollitas cambray al rescoldo, chiles toreados y guacamole.',
                'inst' => "1. Marinar el corte con sal de grano y pimienta negra.\n2. Sellar a fuego directo 4 min por lado a término medio.\n3. Servir con guacamole y chiles toreados.",
                'recipe' => [
                    ['ing' => 'Carne Arrachera Prime', 'qty' => 0.300, 'unit' => 'kg'],
                    ['ing' => 'Aguacate Hass de Primera', 'qty' => 0.120, 'unit' => 'kg'],
                    ['ing' => 'Carbón de Quebracho Blanco', 'qty' => 0.500, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Bife de Chorizo Angus Madurado (400g)',
                'cat'  => 'cortes-y-parrilladas',
                'price'=> 420.00,
                'prep' => 22,
                'img'  => 'https://images.unsplash.com/photo-1600891964092-4316c288032e?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Corte argentino de lomo angosto con 28 días de maduración Dry-Aged y cobertura de grasa dorada al quebracho.',
                'inst' => "1. Temperar el corte 15 min antes.\n2. Dorar primero del lado de la grasa y luego sellar 6 min por cara.\n3. Reposar 3 minutos antes de emplatar.",
                'recipe' => [
                    ['ing' => 'Bife de Chorizo Angus', 'qty' => 0.400, 'unit' => 'kg'],
                    ['ing' => 'Papa Harinosa Seleccionada', 'qty' => 0.200, 'unit' => 'kg'],
                    ['ing' => 'Carbón de Quebracho Blanco', 'qty' => 0.600, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Picaña Prime a la Espada (350g)',
                'cat'  => 'cortes-y-parrilladas',
                'price'=> 380.00,
                'prep' => 20,
                'img'  => 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Tapa de cuadril premium asada en espada estilo rodizio con costra de sal marina gruesa y mantequilla de ajo.',
                'inst' => "1. Cortar en medallones gruesos y ensartar en espada en forma de C.\n2. Sellar a brasa viva hasta lograr costra crocante.",
                'recipe' => [
                    ['ing' => 'Picaña Importada Seleccionada', 'qty' => 0.350, 'unit' => 'kg'],
                    ['ing' => 'Yuca Fresca Pelada', 'qty' => 0.200, 'unit' => 'kg'],
                    ['ing' => 'Carbón de Quebracho Blanco', 'qty' => 0.500, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Ojo de Bife / Ribeye High Choice (400g)',
                'cat'  => 'cortes-y-parrilladas',
                'price'=> 460.00,
                'prep' => 22,
                'img'  => 'https://images.unsplash.com/photo-1546964124-0cce460f38ef?auto=format&fit=crop&w=800&q=80',
                'desc' => 'El corte con mayor marmoleo intramuscular, sumamente jugoso, servido en tabla caliente con romero humeante.',
                'inst' => "1. Sellar a parrilla muy caliente para caramelizar el marmoleo.\n2. Perfumar con rama de romero sobre brasa al servir.",
                'recipe' => [
                    ['ing' => 'Ojo de Bife (Ribeye Prime)', 'qty' => 0.400, 'unit' => 'kg'],
                    ['ing' => 'Espárragos Verdes Frescos', 'qty' => 0.100, 'unit' => 'kg'],
                    ['ing' => 'Carbón de Quebracho Blanco', 'qty' => 0.600, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Tomahawk Steak Flameado en Mesa (900g)',
                'cat'  => 'cortes-y-parrilladas',
                'price'=> 980.00,
                'prep' => 30,
                'img'  => 'https://images.unsplash.com/photo-1594041680534-e8c8cdebd659?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Imponente corte con hueso largo de costilla de 900g, sellado al quebracho y flameado en tu mesa con mantequilla de hierbas.',
                'inst' => "1. Sellar en parrilla fuerte 5 min por lado.\n2. Terminar en horno de brasa hasta 54°C internos.\n3. Trinchado y flameado frente al comensal.",
                'recipe' => [
                    ['ing' => 'Tomahawk Steak con Hueso', 'qty' => 0.900, 'unit' => 'kg'],
                    ['ing' => 'Papa Harinosa Seleccionada', 'qty' => 0.350, 'unit' => 'kg'],
                    ['ing' => 'Espárragos Verdes Frescos', 'qty' => 0.150, 'unit' => 'kg'],
                    ['ing' => 'Carbón de Quebracho Blanco', 'qty' => 1.000, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Asado de Tira Cocción Lenta (450g)',
                'cat'  => 'cortes-y-parrilladas',
                'price'=> 360.00,
                'prep' => 25,
                'img'  => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Tira de costilla transversal asada lentamente durante 4 horas a fuego indirecto de leña, tierna al desprenderse del hueso.',
                'inst' => "1. Asar del lado del hueso durante el 70% del tiempo.\n2. Dorar cara superior y pincelar con salmuera criolla.",
                'recipe' => [
                    ['ing' => 'Asado de Tira / Short Rib', 'qty' => 0.450, 'unit' => 'kg'],
                    ['ing' => 'Yuca Fresca Pelada', 'qty' => 0.250, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Costillar de Cerdo BBQ al Ahumador (500g)',
                'cat'  => 'cortes-y-parrilladas',
                'price'=> 340.00,
                'prep' => 20,
                'img'  => 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Medio rack de costillas St. Louis ahumadas con leña de manzano y glaseadas con salsa BBQ artesanal de bourbon y miel.',
                'inst' => "1. Regenerar costillar ahumado en parrilla.\n2. Caramelizar con doble capa de BBQ casera y servir con papas rústicas.",
                'recipe' => [
                    ['ing' => 'Costillar de Cerdo St. Louis', 'qty' => 0.500, 'unit' => 'kg'],
                    ['ing' => 'Papa Harinosa Seleccionada', 'qty' => 0.250, 'unit' => 'kg'],
                ],
            ],

            // ── PARRILLADAS PARA COMPARTIR (3) ──
            [
                'name' => 'Parrillada Las Brazas Imperial (4 Personas)',
                'cat'  => 'parrilladas-para-compartir',
                'price'=> 1250.00,
                'prep' => 30,
                'img'  => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=800&q=80',
                'desc' => '1.4 kg de selección maestra: Bife de Chorizo, Arrachera Prime, Picaña, Asado de Tira, 2 Chorizos Argentinos, Chistorra, Queso Fundido y 3 guarniciones.',
                'inst' => "1. Montar brasero de mesa con carbón encendido.\n2. Disponer los cortes sellados en su punto junto con cazuela de queso y guarniciones.",
                'recipe' => [
                    ['ing' => 'Carne Arrachera Prime', 'qty' => 0.350, 'unit' => 'kg'],
                    ['ing' => 'Bife de Chorizo Angus', 'qty' => 0.350, 'unit' => 'kg'],
                    ['ing' => 'Picaña Importada Seleccionada', 'qty' => 0.350, 'unit' => 'kg'],
                    ['ing' => 'Chorizo Argentino Artesanal', 'qty' => 0.300, 'unit' => 'kg'],
                    ['ing' => 'Queso Gouda para Fundir', 'qty' => 0.200, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Tabla Gaucha Mixta (2 Personas)',
                'cat'  => 'parrilladas-para-compartir',
                'price'=> 680.00,
                'prep' => 25,
                'img'  => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80',
                'desc' => '750g de carnes a la brasa (Picaña, Arrachera y Costilla), chorizo artesanal, provoleta asada, papas rústicas y yuca frita.',
                'inst' => "1. Asar picaña y arrachera al término solicitado.\n2. Servir en tabla de madera de quebracho con provoleta al centro.",
                'recipe' => [
                    ['ing' => 'Picaña Importada Seleccionada', 'qty' => 0.300, 'unit' => 'kg'],
                    ['ing' => 'Carne Arrachera Prime', 'qty' => 0.300, 'unit' => 'kg'],
                    ['ing' => 'Chorizo Argentino Artesanal', 'qty' => 0.150, 'unit' => 'kg'],
                    ['ing' => 'Queso Provolone Ahumado', 'qty' => 0.150, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Banquete Ejecutivo VIP + Malbec (4-6 Personas)',
                'cat'  => 'parrilladas-para-compartir',
                'price'=> 1850.00,
                'prep' => 35,
                'img'  => 'https://images.unsplash.com/photo-1558030006-450675393462?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Incluye 1 Tomahawk de 900g, 400g de Ojo de Bife, 400g de Picaña, Provoleta, Guacamole, Espárragos y 1 Botella de Vino Malbec Reserva.',
                'inst' => "1. Preparar servicio completo de mesa VIP.\n2. Descorchar y decantar Malbec Reserva mientras se trinchan los cortes.",
                'recipe' => [
                    ['ing' => 'Tomahawk Steak con Hueso', 'qty' => 0.900, 'unit' => 'kg'],
                    ['ing' => 'Ojo de Bife (Ribeye Prime)', 'qty' => 0.400, 'unit' => 'kg'],
                    ['ing' => 'Picaña Importada Seleccionada', 'qty' => 0.400, 'unit' => 'kg'],
                    ['ing' => 'Vino Malbec Reserva Mendoza', 'qty' => 1.000, 'unit' => 'bot'],
                ],
            ],

            // ── HAMBURGUESAS & SÁNDWICHES (4) ──
            [
                'name' => 'Hamburguesa Wagyu Smokehouse (250g)',
                'cat'  => 'hamburguesas-y-sandwiches',
                'price'=> 245.00,
                'prep' => 18,
                'img'  => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Medallón de 250g de blend Wagyu/Sirloin a la brasa, doble queso gouda, cebolla caramelizada al whisky, tocino y pan brioche dorado.',
                'inst' => "1. Asar medallón a fuego fuerte 4 min por lado.\n2. Fundir queso en campana con humo de quebracho y servir con papas.",
                'recipe' => [
                    ['ing' => 'Blend Hamburguesa Wagyu/Sirloin', 'qty' => 0.250, 'unit' => 'kg'],
                    ['ing' => 'Queso Gouda para Fundir', 'qty' => 0.060, 'unit' => 'kg'],
                    ['ing' => 'Pan Brioche Artesanal', 'qty' => 1.000, 'unit' => 'pza'],
                    ['ing' => 'Papa Harinosa Seleccionada', 'qty' => 0.200, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Choripán Gaucho con Chimichurri',
                'cat'  => 'hamburguesas-y-sandwiches',
                'price'=> 140.00,
                'prep' => 14,
                'img'  => 'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Chorizo argentino mariposa asado a las brasas en pan crujiente con queso provolone gratinado, salsa criolla y chimichurri de la casa.',
                'inst' => "1. Abrir chorizo en corte mariposa y dorar cara interna.\n2. Gratinar con provolone y montar en pan tostado a la parrilla.",
                'recipe' => [
                    ['ing' => 'Chorizo Argentino Artesanal', 'qty' => 0.200, 'unit' => 'kg'],
                    ['ing' => 'Queso Provolone Ahumado', 'qty' => 0.050, 'unit' => 'kg'],
                    ['ing' => 'Pan Brioche Artesanal', 'qty' => 1.000, 'unit' => 'pza'],
                ],
            ],
            [
                'name' => 'Sándwich de Picaña Ahumada en Ciabatta',
                'cat'  => 'hamburguesas-y-sandwiches',
                'price'=> 220.00,
                'prep' => 16,
                'img'  => 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Láminas finas de picaña jugosa a término medio, queso gouda fundido, rúcula fresca, mayonesa de ajo rostizado y papas fritas.',
                'inst' => "1. Filetear picaña sellada a contrafibra.\n2. Fundir queso sobre la carne en plancha y montar en pan caliente.",
                'recipe' => [
                    ['ing' => 'Picaña Importada Seleccionada', 'qty' => 0.200, 'unit' => 'kg'],
                    ['ing' => 'Queso Gouda para Fundir', 'qty' => 0.060, 'unit' => 'kg'],
                    ['ing' => 'Pan Brioche Artesanal', 'qty' => 1.000, 'unit' => 'pza'],
                ],
            ],
            [
                'name' => 'Burger Brisket & Provolone Fundido',
                'cat'  => 'hamburguesas-y-sandwiches',
                'price'=> 235.00,
                'prep' => 18,
                'img'  => 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Hamburguesa de res coronada con hebras de asado de tira deshebrado, provolone ahumado y salsa BBQ de la casa.',
                'inst' => "1. Sellar burger en parrilla.\n2. Coronar con hebras de carne ahumada y provolone fundido.",
                'recipe' => [
                    ['ing' => 'Blend Hamburguesa Wagyu/Sirloin', 'qty' => 0.200, 'unit' => 'kg'],
                    ['ing' => 'Asado de Tira / Short Rib', 'qty' => 0.100, 'unit' => 'kg'],
                    ['ing' => 'Queso Provolone Ahumado', 'qty' => 0.060, 'unit' => 'kg'],
                    ['ing' => 'Pan Brioche Artesanal', 'qty' => 1.000, 'unit' => 'pza'],
                ],
            ],

            // ── GUARNICIONES & ENSALADAS (4) ──
            [
                'name' => 'Papas Rústicas al Romero y Parmesano',
                'cat'  => 'guarniciones-y-ensaladas',
                'price'=> 85.00,
                'prep' => 10,
                'img'  => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Gajos de papa harinosa con piel en doble cocción, salteados con mantequilla de ajo, romero fresco y lluvia de queso.',
                'inst' => "1. Freír gajos de papa a 180°C hasta dorar.\n2. Saltear en sartén con mantequilla de romero y sal en escamas.",
                'recipe' => [
                    ['ing' => 'Papa Harinosa Seleccionada', 'qty' => 0.350, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Espárragos Verdes a la Parrilla',
                'cat'  => 'guarniciones-y-ensaladas',
                'price'=> 110.00,
                'prep' => 10,
                'img'  => 'https://images.unsplash.com/photo-1515543237350-b3eea1ec8082?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Manojo de espárragos tiernos asados al carbón con aceite de oliva extra virgen, escamas de sal marina y toque de limón.',
                'inst' => "1. Blanquear 1 minuto y pasar a parrilla bien caliente por 3 minutos.\n2. Aderezar con limón real y aceite de oliva.",
                'recipe' => [
                    ['ing' => 'Espárragos Verdes Frescos', 'qty' => 0.220, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Yuca Frita Crocante a la Huancaína',
                'cat'  => 'guarniciones-y-ensaladas',
                'price'=> 75.00,
                'prep' => 10,
                'img'  => 'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Bastones de yuca tierna por dentro y extra crujientes por fuera, acompañados de emulsión de ajo y crema de queso.',
                'inst' => "1. Freír bastones de yuca cocida hasta lograr dorado uniforme.\n2. Servir con dip cremoso.",
                'recipe' => [
                    ['ing' => 'Yuca Fresca Pelada', 'qty' => 0.350, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Guacamole Martajado en Molcajete',
                'cat'  => 'guarniciones-y-ensaladas',
                'price'=> 120.00,
                'prep' => 8,
                'img'  => 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Aguacate Hass fresco preparado al momento con pico de gallo, cilantro criollo, aceite de oliva y totopos de maíz.',
                'inst' => "1. Martajar pulpa de aguacate Hass en molcajete.\n2. Integrar limón, cilantro, cebolla morada y sal de grano.",
                'recipe' => [
                    ['ing' => 'Aguacate Hass de Primera', 'qty' => 0.300, 'unit' => 'kg'],
                    ['ing' => 'Limón Real & Hierbabuena', 'qty' => 0.050, 'unit' => 'kg'],
                ],
            ],

            // ── POSTRES ARTESANALES (3) ──
            [
                'name' => 'Volcán de Chocolate 70% con Helado',
                'cat'  => 'postres-artesanales',
                'price'=> 135.00,
                'prep' => 15,
                'img'  => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Bizcocho tibio de chocolate semiamargo con centro líquido fundido, acompañado de helado artesanal de vainilla y frutos rojos.',
                'inst' => "1. Hornear a 200°C exactamente 11 minutos.\n2. Desmoldar en plato tibio y coronar con bocha de helado.",
                'recipe' => [
                    ['ing' => 'Chocolate Semiamargo 70%', 'qty' => 0.120, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Panqueque de Dulce de Leche Flameado',
                'cat'  => 'postres-artesanales',
                'price'=> 115.00,
                'prep' => 12,
                'img'  => 'https://images.unsplash.com/photo-1519676867240-f03562e64548?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Clásico argentino: crepas finas rellenas de abundante dulce de leche repostero, caramelizadas a fuego con ron añejo.',
                'inst' => "1. Rellenar crepas con dulce de leche.\n2. Espolvorear azúcar, quemar con plancha al rojo vivo y flamear.",
                'recipe' => [
                    ['ing' => 'Dulce de Leche Repostero', 'qty' => 0.150, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Flan Napolitano al Caramelo de Ron',
                'cat'  => 'postres-artesanales',
                'price'=> 95.00,
                'prep' => 5,
                'img'  => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Textura ultra cremosa horneada a baño maría con caramelo oscuro de vainilla natural y nueces tostadas.',
                'inst' => "1. Desmoldar flan frío.\n2. Bañar con salsa de caramelo y decorar con crema batida.",
                'recipe' => [
                    ['ing' => 'Dulce de Leche Repostero', 'qty' => 0.080, 'unit' => 'kg'],
                ],
            ],

            // ── BEBIDAS (2) ──
            [
                'name' => 'Limonada Frappé de Hierbabuena (Jarra 1L)',
                'cat'  => 'bebidas',
                'price'=> 110.00,
                'prep' => 6,
                'img'  => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Refrescante jarra de 1 litro preparada al momento con zumo de limón real, hojas de hierbabuena fresca y toque de jengibre.',
                'inst' => "1. Licuar hielo frappé con jugo de limón real, almíbar ligero y hojas de hierbabuena.",
                'recipe' => [
                    ['ing' => 'Limón Real & Hierbabuena', 'qty' => 0.350, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Agua Mineral / Refresco Línea (500ml)',
                'cat'  => 'bebidas',
                'price'=> 45.00,
                'prep' => 2,
                'img'  => 'https://images.unsplash.com/photo-1556881286-fc6915169721?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Servido bien frío en copa con hielo cristalino y rodaja de limón.',
                'inst' => "1. Servir en vaso de cristal con hielo y rodaja de cítrico.",
                'recipe' => [
                    ['ing' => 'Limón Real & Hierbabuena', 'qty' => 0.030, 'unit' => 'kg'],
                ],
            ],

            // ── COCTELERÍA & VINOS (2) ──
            [
                'name' => 'Botella Vino Malbec Reserva Mendoza',
                'cat'  => 'cocteleria-y-vinos',
                'price'=> 480.00,
                'prep' => 5,
                'img'  => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Vino tinto de guarda (12 meses en barrica de roble francés), notas de ciruela madura, vainilla y taninos redondos ideales para cortes.',
                'inst' => "1. Presentar botella al comensal a 16-18°C.\n2. Descorchar y servir en copas Bordeaux.",
                'recipe' => [
                    ['ing' => 'Vino Malbec Reserva Mendoza', 'qty' => 1.000, 'unit' => 'bot'],
                ],
            ],
            [
                'name' => 'Cerveza Artesanal IPA / Roja de la Casa',
                'cat'  => 'cocteleria-y-vinos',
                'price'=> 95.00,
                'prep' => 3,
                'img'  => 'https://images.unsplash.com/photo-1535958636474-b021ee887b13?auto=format&fit=crop&w=800&q=80',
                'desc' => 'Cerveza de autor con cuerpo maltoso y notas cítricas de lúpulo seleccionada especialmente para maridar carnes ahumadas.',
                'inst' => "1. Servir en pinta escarchada a 4°C.",
                'recipe' => [
                    ['ing' => 'Cerveza Artesanal IPA / Roja', 'qty' => 1.000, 'unit' => 'bot'],
                ],
            ],
        ];

        $dishes = [];
        foreach ($dishesData as $d) {
            $dish = Dish::updateOrCreate(
                ['name' => $d['name']],
                [
                    'category_id'       => $categories[$d['cat']]->id,
                    'description'       => $d['desc'],
                    'price'             => $d['price'],
                    'image_url'         => $d['img'],
                    'prep_time_minutes' => $d['prep'],
                    'is_available'      => true,
                ]
            );
            $dishes[$d['name']] = $dish;

            // Crear Ficha Técnica (Receta e Ingredientes)
            $recipe = Recipe::updateOrCreate(
                ['dish_id' => $dish->id],
                ['instructions' => $d['inst']]
            );

            foreach ($d['recipe'] as $rIng) {
                if (isset($ingredients[$rIng['ing']])) {
                    RecipeIngredient::updateOrCreate(
                        [
                            'recipe_id'     => $recipe->id,
                            'ingredient_id' => $ingredients[$rIng['ing']]->id,
                        ],
                        [
                            'quantity_required' => $rIng['qty'],
                            'unit_of_measure'   => $rIng['unit'],
                        ]
                    );
                }
            }
        }

        // =====================================================================
        // 8. CLIENTES FRECUENTES (10 CLIENTES) + DIRECCIONES GPS (APP MÓVIL)
        // =====================================================================
        $customersData = [
            [
                'name'  => 'Lic. Carlos Mendoza',
                'email' => 'carlos.mendoza@empresa.com',
                'phone' => '710-88331',
                'notes' => 'Cliente VIP. Prefiere mesa en Terraza. Término de la carne 3/4. Maridaje favorito: Malbec.',
                'addr'  => ['label' => 'Oficina Corporativa', 'street' => 'Av. Equipetrol Norte, Calle 4 Este', 'number' => '#210', 'floor' => 'Piso 8, Of. 802', 'ref' => 'Torre Empresarial Platinum, frente al Hotel', 'lat' => -17.7642, 'lng' => -63.1965],
            ],
            [
                'name'  => 'Dra. Mariana Silva',
                'email' => 'mariana.silva@gmail.com',
                'phone' => '710-19282',
                'notes' => 'Alergia a los mariscos y frutos secos. Le encanta la Picaña y las ensaladas frescas.',
                'addr'  => ['label' => 'Casa', 'street' => 'Av. Las Américas', 'number' => '#450', 'floor' => 'Edif. Torre Sol, Piso 4B', 'ref' => 'Frente al Parque Urbano, portón de vidrio', 'lat' => -17.7833, 'lng' => -63.1821],
            ],
            [
                'name'  => 'Arq. Roberto Justiniano',
                'email' => 'roberto.j@arquitectura.bo',
                'phone' => '710-44510',
                'notes' => 'Suele reservar Salón VIP para cenas de negocios de 6 a 10 personas.',
                'addr'  => ['label' => 'Estudio Arquitectura', 'street' => 'Av. San Martín, Pasillo 2', 'number' => '#118', 'floor' => 'Planta Baja', 'ref' => 'Diagonal a la plaza comercial', 'lat' => -17.7710, 'lng' => -63.1912],
            ],
            [
                'name'  => 'Lic. Sofía Landívar',
                'email' => 'sofia.landivar@finanzas.com',
                'phone' => '710-99201',
                'notes' => 'Pide frecuentemente por Delivery los fines de semana. Prefiere cortes punto medio.',
                'addr'  => ['label' => 'Condominio Las Palmas', 'street' => 'Av. Doble Vía La Guardia Km 6', 'number' => 'Casa #14', 'floor' => null, 'ref' => 'Condominio cerrado, anunciar en portería', 'lat' => -17.8012, 'lng' => -63.2140],
            ],
            [
                'name'  => 'Dr. Andrés Cronenbold',
                'email' => 'a.cronenbold@clinica.com',
                'phone' => '721-55012',
                'notes' => 'Fanático del Tomahawk Steak y el Ojo de Bife. Celebra aniversarios familiares aquí.',
                'addr'  => ['label' => 'Domicilio Urbarí', 'street' => 'Calle Barachavi', 'number' => '#340', 'floor' => null, 'ref' => 'Portón de madera color caoba', 'lat' => -17.7920, 'lng' => -63.1990],
            ],
            [
                'name'  => 'Ing. Patricia Saucedo',
                'email' => 'patricia.saucedo@petrolera.bo',
                'phone' => '750-33190',
                'notes' => 'Prefiere mesas tranquilas en Salón General lejos de las pantallas.',
                'addr'  => ['label' => 'Departamento', 'street' => 'Av. Beni entre 3er y 4to Anillo', 'number' => '#890', 'floor' => 'Dpto 3C', 'ref' => 'Condominio Jardines del Norte', 'lat' => -17.7520, 'lng' => -63.1750],
            ],
            [
                'name'  => 'Fam. Gutiérrez Kempff',
                'email' => 'gutierrez.familia@gmail.com',
                'phone' => '760-11209',
                'notes' => 'Asisten los domingos al mediodía con niños. Solicitan sillas altas.',
                'addr'  => ['label' => 'Casa Familiar', 'street' => 'Av. Banzer Km 5, Calle Los Pinos', 'number' => '#55', 'floor' => null, 'ref' => 'Esquina con barda blanca', 'lat' => -17.7410, 'lng' => -63.1680],
            ],
            [
                'name'  => 'Lic. Mauricio Paz',
                'email' => 'mauricio.paz@agencia.com',
                'phone' => '773-90811',
                'notes' => 'Cliente frecuente de Barra & Lounge y hamburguesas Wagyu.',
                'addr'  => ['label' => 'Loft Centro', 'street' => 'Calle Libertad', 'number' => '#412', 'floor' => 'Piso 2', 'ref' => 'A dos cuadras de la Plaza 24 de Septiembre', 'lat' => -17.7815, 'lng' => -63.1810],
            ],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $cust = Customer::updateOrCreate(
                ['email' => $c['email']],
                [
                    'name'              => $c['name'],
                    'phone'             => $c['phone'],
                    'preferences_notes' => $c['notes'],
                ]
            );
            $customers[$c['email']] = $cust;

            if (!empty($c['addr'])) {
                CustomerAddress::updateOrCreate(
                    ['customer_id' => $cust->id, 'label' => $c['addr']['label']],
                    [
                        'street'     => $c['addr']['street'],
                        'number'     => $c['addr']['number'],
                        'floor'      => $c['addr']['floor'],
                        'reference'  => $c['addr']['ref'],
                        'latitude'   => $c['addr']['lat'],
                        'longitude'  => $c['addr']['lng'],
                        'is_default' => true,
                    ]
                );
            }
        }

        // =====================================================================
        // 9. RESERVACIONES (8 RESERVACIONES ACTIVAS E HISTÓRICAS)
        // =====================================================================
        $reservationsData = [
            [
                'customer' => 'carlos.mendoza@empresa.com',
                'table'    => 'Mesa 4',
                'area'     => 'Terraza Principal',
                'date'     => now()->setHour(20)->setMinute(0)->setSecond(0),
                'guests'   => 4,
                'children' => 0,
                'status'   => 'confirmed',
                'requests' => 'Descorche de Malbec al llegar. Mesa con vista al jardín.',
            ],
            [
                'customer' => 'roberto.j@arquitectura.bo',
                'table'    => 'Mesa VIP 3',
                'area'     => 'Salón VIP & Cava',
                'date'     => now()->setHour(21)->setMinute(0)->setSecond(0),
                'guests'   => 10,
                'children' => 0,
                'status'   => 'confirmed',
                'requests' => 'Cena ejecutiva de cierre de proyecto. Requerimos factura corporativa.',
            ],
            [
                'customer' => 'patricia.saucedo@petrolera.bo',
                'table'    => 'Mesa 10',
                'area'     => 'Salón General',
                'date'     => now()->addDay()->setHour(13)->setMinute(30)->setSecond(0),
                'guests'   => 5,
                'children' => 1,
                'status'   => 'confirmed',
                'requests' => 'Almuerzo de cumpleaños, preparar postre con vela.',
            ],
            [
                'customer' => 'mariana.silva@gmail.com',
                'table'    => 'Mesa 2',
                'area'     => 'Terraza Principal',
                'date'     => now()->addDay()->setHour(20)->setMinute(30)->setSecond(0),
                'guests'   => 4,
                'children' => 0,
                'status'   => 'pending',
                'requests' => 'Cuidado estricto con alergia a frutos secos en ensaladas.',
            ],
            [
                'customer' => 'gutierrez.familia@gmail.com',
                'table'    => 'Mesa 6',
                'area'     => 'Terraza Principal',
                'date'     => now()->addDays(2)->setHour(13)->setMinute(0)->setSecond(0),
                'guests'   => 7,
                'children' => 2,
                'status'   => 'confirmed',
                'requests' => '2 sillas para niños pequeños por favor.',
            ],
            [
                'customer' => 'a.cronenbold@clinica.com',
                'table'    => 'Mesa VIP 2',
                'area'     => 'Salón VIP & Cava',
                'date'     => now()->subDay()->setHour(20)->setMinute(0)->setSecond(0),
                'guests'   => 8,
                'children' => 0,
                'status'   => 'completed',
                'requests' => 'Aniversario familiar.',
            ],
        ];

        foreach ($reservationsData as $r) {
            $cust = $customers[$r['customer']] ?? null;
            $tbl  = $tables[$r['table']] ?? null;
            $area = $areas[$r['area']] ?? null;
            if ($cust && $tbl) {
                Reservation::updateOrCreate(
                    [
                        'customer_id'      => $cust->id,
                        'table_id'         => $tbl->id,
                        'reservation_date' => $r['date'],
                    ],
                    [
                        'area_id'          => $area?->id,
                        'user_id'          => $adminUser->id,
                        'guest_count'      => $r['guests'],
                        'children_count'   => $r['children'],
                        'status'           => $r['status'],
                        'special_requests' => $r['requests'],
                    ]
                );
            }
        }

        // =====================================================================
        // 10. MOVIMIENTOS DE INVENTARIO Y MERMAS
        // =====================================================================
        $movementsData = [
            ['ing' => 'Carne Arrachera Prime',          'type' => 'in',    'qty' => 35.000, 'reason' => 'Compra inicial de lote semanal frigorífico'],
            ['ing' => 'Carne Arrachera Prime',          'type' => 'out',   'qty' => 6.500,  'reason' => 'Consumo por preparación de comandas fin de semana'],
            ['ing' => 'Carne Arrachera Prime',          'type' => 'waste', 'qty' => 0.650,  'reason' => 'Merma por recorte de membrana y grasa periférica'],
            ['ing' => 'Bife de Chorizo Angus',          'type' => 'in',    'qty' => 25.000, 'reason' => 'Abastecimiento de cortes Dry-Aged'],
            ['ing' => 'Bife de Chorizo Angus',          'type' => 'waste', 'qty' => 0.450,  'reason' => 'Merma por deshidratación de corteza de maduración'],
            ['ing' => 'Picaña Importada Seleccionada',  'type' => 'in',    'qty' => 24.000, 'reason' => 'Recepción de lote importado'],
            ['ing' => 'Picaña Importada Seleccionada',  'type' => 'out',   'qty' => 4.600,  'reason' => 'Consumo servicio de salón y delivery'],
            ['ing' => 'Aguacate Hass de Primera',       'type' => 'waste', 'qty' => 0.800,  'reason' => 'Merma por oxidación de piezas maduras'],
            ['ing' => 'Espárragos Verdes Frescos',      'type' => 'waste', 'qty' => 0.350,  'reason' => 'Tallos fibrosos descartados en limpieza'],
            ['ing' => 'Carbón de Quebracho Blanco',     'type' => 'in',    'qty' => 150.000,'reason' => 'Entrega mensual de sacos de quebracho'],
            ['ing' => 'Carbón de Quebracho Blanco',     'type' => 'out',   'qty' => 30.000, 'reason' => 'Encendido de parrillas central y ahumador'],
        ];

        foreach ($movementsData as $m) {
            if (isset($ingredients[$m['ing']])) {
                InventoryMovement::updateOrCreate(
                    ['reason' => $m['reason'], 'ingredient_id' => $ingredients[$m['ing']]->id],
                    [
                        'user_id'       => $adminUser->id,
                        'movement_type' => $m['type'],
                        'quantity'      => $m['qty'],
                    ]
                );
            }
        }

        // =====================================================================
        // 11. CUPONES PROMOCIONALES (5 CUPONES PARA APP MÓVIL Y POS)
        // =====================================================================
        $couponsData = [
            ['code' => 'BRAZAS10',     'type' => 'percent',       'value' => 10,  'min_order' => 100],
            ['code' => 'ENVIOGRATIS',  'type' => 'free_shipping', 'value' => 0,   'min_order' => 50],
            ['code' => 'VIP20',        'type' => 'percent',       'value' => 20,  'min_order' => 500],
            ['code' => 'PARRILLA50',   'type' => 'fixed',         'value' => 50,  'min_order' => 350],
            ['code' => 'BIENVENIDA15', 'type' => 'percent',       'value' => 15,  'min_order' => 150],
        ];

        foreach ($couponsData as $cp) {
            Coupon::updateOrCreate(
                ['code' => $cp['code']],
                [
                    'type'      => $cp['type'],
                    'value'     => $cp['value'],
                    'min_order' => $cp['min_order'],
                    'is_active' => true,
                ]
            );
        }

        // =====================================================================
        // 12. COMANDAS EN VIVO EN SALÓN (3 MESAS ACTIVAS EN POS Y KDS)
        // =====================================================================
        // 12.1 Mesa 1 (En Cocina)
        $orderMesa1 = Order::updateOrCreate(
            ['table_id' => $tables['Mesa 1']->id, 'status' => 'in_kitchen'],
            [
                'user_id'      => $waiter1->id,
                'customer_id'  => $customers['carlos.mendoza@empresa.com']->id,
                'order_type'   => 'dine_in',
                'subtotal'     => 575.00,
                'tax'          => 0,
                'total_amount' => 575.00,
                'notes'        => 'Cliente VIP en Terraza',
                'created_at'   => now()->subMinutes(14),
                'updated_at'   => now(),
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $orderMesa1->id, 'dish_id' => $dishes['Arrachera Las Brazas (300g)']->id],
            ['quantity' => 1, 'unit_price' => 320.00, 'subtotal' => 320.00, 'notes' => 'Término 3/4, chiles bien toreados', 'status' => 'cooking']
        );
        OrderItem::updateOrCreate(
            ['order_id' => $orderMesa1->id, 'dish_id' => $dishes['Queso Fundido con Chistorra']->id],
            ['quantity' => 1, 'unit_price' => 145.00, 'subtotal' => 145.00, 'notes' => 'Tortillas de harina extras', 'status' => 'ready']
        );
        OrderItem::updateOrCreate(
            ['order_id' => $orderMesa1->id, 'dish_id' => $dishes['Limonada Frappé de Hierbabuena (Jarra 1L)']->id],
            ['quantity' => 1, 'unit_price' => 110.00, 'subtotal' => 110.00, 'notes' => 'Poca azúcar', 'status' => 'ready']
        );

        // 12.2 Mesa 3 (En Cocina)
        $orderMesa3 = Order::updateOrCreate(
            ['table_id' => $tables['Mesa 3']->id, 'status' => 'in_kitchen'],
            [
                'user_id'      => $waiter2->id,
                'customer_id'  => $customers['mauricio.paz@agencia.com']->id,
                'order_type'   => 'dine_in',
                'subtotal'     => 995.00,
                'tax'          => 0,
                'total_amount' => 995.00,
                'notes'        => 'Almuerzo de equipo',
                'created_at'   => now()->subMinutes(9),
                'updated_at'   => now(),
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $orderMesa3->id, 'dish_id' => $dishes['Tabla Gaucha Mixta (2 Personas)']->id],
            ['quantity' => 1, 'unit_price' => 680.00, 'subtotal' => 680.00, 'notes' => 'Término medio en la picaña', 'status' => 'cooking']
        );
        OrderItem::updateOrCreate(
            ['order_id' => $orderMesa3->id, 'dish_id' => $dishes['Guacamole Martajado en Molcajete']->id],
            ['quantity' => 1, 'unit_price' => 120.00, 'subtotal' => 120.00, 'notes' => 'Con totopos extra', 'status' => 'ready']
        );
        OrderItem::updateOrCreate(
            ['order_id' => $orderMesa3->id, 'dish_id' => $dishes['Cerveza Artesanal IPA / Roja de la Casa']->id],
            ['quantity' => 2, 'unit_price' => 95.00, 'subtotal' => 190.00, 'notes' => '1 IPA y 1 Roja bien frías', 'status' => 'ready']
        );

        // 12.3 Mesa VIP 1 (Lista para servir)
        $orderVip1 = Order::updateOrCreate(
            ['table_id' => $tables['Mesa VIP 1']->id, 'status' => 'ready'],
            [
                'user_id'      => $waiter1->id,
                'customer_id'  => $customers['a.cronenbold@clinica.com']->id,
                'order_type'   => 'dine_in',
                'subtotal'     => 1670.00,
                'tax'          => 0,
                'total_amount' => 1670.00,
                'notes'        => 'Servicio prioritario Salón VIP',
                'created_at'   => now()->subMinutes(22),
                'updated_at'   => now(),
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $orderVip1->id, 'dish_id' => $dishes['Tomahawk Steak Flameado en Mesa (900g)']->id],
            ['quantity' => 1, 'unit_price' => 980.00, 'subtotal' => 980.00, 'notes' => 'Flamear en mesa frente a los invitados', 'status' => 'ready']
        );
        OrderItem::updateOrCreate(
            ['order_id' => $orderVip1->id, 'dish_id' => $dishes['Tuétanos Rostizados al Carbón']->id],
            ['quantity' => 1, 'unit_price' => 210.00, 'subtotal' => 210.00, 'notes' => 'Al centro como entrada', 'status' => 'ready']
        );
        OrderItem::updateOrCreate(
            ['order_id' => $orderVip1->id, 'dish_id' => $dishes['Botella Vino Malbec Reserva Mendoza']->id],
            ['quantity' => 1, 'unit_price' => 480.00, 'subtotal' => 480.00, 'notes' => 'Traer 4 copas Bordeaux', 'status' => 'ready']
        );

        // =====================================================================
        // 13. PEDIDOS ACTIVOS DE APP MÓVIL (DELIVERY Y TAKEAWAY)
        // =====================================================================
        // 13.1 Pedido Delivery #1 (Listo para despacho - PIN 4821)
        $delOrder1 = Order::updateOrCreate(
            ['order_type' => 'delivery', 'delivery_pin' => '4821'],
            [
                'customer_id'      => $customers['mariana.silva@gmail.com']->id,
                'status'           => 'ready',
                'subtotal'         => 760.00,
                'tax'              => 0,
                'delivery_fee'     => 0.00,
                'discount'         => 0.00,
                'coupon_code'      => 'ENVIOGRATIS',
                'total_amount'     => 760.00,
                'delivery_address' => 'Av. Las Américas #450, Edificio Torre Sol, Piso 4B (Frente al Parque)',
                'delivery_lat'     => -17.7833,
                'delivery_lng'     => -63.1821,
                'invoice_name'     => 'Dra. Mariana Silva',
                'invoice_tax_id'   => '4829104015',
                'notes'            => 'Pedido desde App Móvil Flutter - Llamar al llegar a recepción',
                'created_at'       => now()->subMinutes(18),
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $delOrder1->id, 'dish_id' => $dishes['Picaña Prime a la Espada (350g)']->id],
            ['quantity' => 2, 'unit_price' => 380.00, 'subtotal' => 760.00, 'notes' => 'Término medio, chimichurri aparte', 'status' => 'ready']
        );

        // 13.2 Pedido Delivery #2 (En Cocina - PIN 7390)
        $delOrder2 = Order::updateOrCreate(
            ['order_type' => 'delivery', 'delivery_pin' => '7390'],
            [
                'customer_id'      => $customers['sofia.landivar@finanzas.com']->id,
                'status'           => 'in_kitchen',
                'subtotal'         => 625.00,
                'tax'              => 0,
                'delivery_fee'     => 15.00,
                'discount'         => 62.50,
                'coupon_code'      => 'BRAZAS10',
                'total_amount'     => 577.50,
                'delivery_address' => 'Av. Doble Vía La Guardia Km 6, Condominio Las Palmas, Casa #14',
                'delivery_lat'     => -17.8012,
                'delivery_lng'     => -63.2140,
                'invoice_name'     => 'Landívar Consultores SRL',
                'invoice_tax_id'   => '1029384022',
                'notes'            => 'Enviar cubiertos y servilletas para 3 personas',
                'created_at'       => now()->subMinutes(11),
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $delOrder2->id, 'dish_id' => $dishes['Hamburguesa Wagyu Smokehouse (250g)']->id],
            ['quantity' => 2, 'unit_price' => 245.00, 'subtotal' => 490.00, 'notes' => 'Una sin cebolla caramelizada', 'status' => 'cooking']
        );
        OrderItem::updateOrCreate(
            ['order_id' => $delOrder2->id, 'dish_id' => $dishes['Volcán de Chocolate 70% con Helado']->id],
            ['quantity' => 1, 'unit_price' => 135.00, 'subtotal' => 135.00, 'notes' => 'Helado en envase térmico aparte', 'status' => 'pending']
        );

        // 13.3 Pedido Delivery #3 (Pendiente de confirmar - PIN 9104)
        $delOrder3 = Order::updateOrCreate(
            ['order_type' => 'delivery', 'delivery_pin' => '9104'],
            [
                'customer_id'      => $customers['carlos.mendoza@empresa.com']->id,
                'status'           => 'pending',
                'subtotal'         => 1250.00,
                'tax'              => 0,
                'delivery_fee'     => 12.00,
                'discount'         => 0.00,
                'total_amount'     => 1262.00,
                'delivery_address' => 'Av. Equipetrol Norte, Calle 4 Este #210, Torre Empresarial Platinum Piso 8',
                'delivery_lat'     => -17.7642,
                'delivery_lng'     => -63.1965,
                'invoice_name'     => 'Grupo Empresarial Mendoza SA',
                'invoice_tax_id'   => '309481029',
                'notes'            => 'Pedido corporativo desde App Móvil - Entregar en recepción piso 8',
                'created_at'       => now()->subMinutes(4),
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $delOrder3->id, 'dish_id' => $dishes['Parrillada Las Brazas Imperial (4 Personas)']->id],
            ['quantity' => 1, 'unit_price' => 1250.00, 'subtotal' => 1250.00, 'notes' => 'Cortes en término 3/4', 'status' => 'pending']
        );

        // 13.4 Pedido Para Llevar / Takeaway (Listo para recoger en mostrador - PIN 3318)
        $takeawayOrder = Order::updateOrCreate(
            ['order_type' => 'takeaway', 'delivery_pin' => '3318'],
            [
                'customer_id'    => $customers['roberto.j@arquitectura.bo']->id,
                'status'         => 'ready',
                'subtotal'       => 580.00,
                'tax'            => 0,
                'delivery_fee'   => 0.00,
                'total_amount'   => 580.00,
                'invoice_name'   => 'Arq. Roberto Justiniano',
                'invoice_tax_id' => '6281904011',
                'notes'          => 'Takeaway App Móvil - Pasa a recoger en 10 minutos',
                'created_at'     => now()->subMinutes(16),
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $takeawayOrder->id, 'dish_id' => $dishes['Bife de Chorizo Angus Madurado (400g)']->id],
            ['quantity' => 1, 'unit_price' => 420.00, 'subtotal' => 420.00, 'notes' => 'Empaque térmico para llevar', 'status' => 'ready']
        );
        OrderItem::updateOrCreate(
            ['order_id' => $takeawayOrder->id, 'dish_id' => $dishes['Provoleta Ahumada al Orégano']->id],
            ['quantity' => 1, 'unit_price' => 160.00, 'subtotal' => 160.00, 'notes' => 'Bien gratinada', 'status' => 'ready']
        );

        // =====================================================================
        // 14. ÓRDENES PAGADAS HISTÓRICAS (ÚLTIMOS 14 DÍAS) PARA DASHBOARD Y REPORTES IA
        // =====================================================================
        $historicalSchedule = [
            ['daysAgo' => 13, 'hour' => 14, 'type' => 'dine_in',  'method' => 'card',     'items' => [['dish' => 'Arrachera Las Brazas (300g)', 'qty' => 3], ['dish' => 'Queso Fundido con Chistorra', 'qty' => 2]]],
            ['daysAgo' => 12, 'hour' => 20, 'type' => 'dine_in',  'method' => 'cash',     'items' => [['dish' => 'Parrillada Las Brazas Imperial (4 Personas)', 'qty' => 1], ['dish' => 'Botella Vino Malbec Reserva Mendoza', 'qty' => 1]]],
            ['daysAgo' => 11, 'hour' => 13, 'type' => 'delivery', 'method' => 'transfer', 'items' => [['dish' => 'Hamburguesa Wagyu Smokehouse (250g)', 'qty' => 4], ['dish' => 'Limonada Frappé de Hierbabuena (Jarra 1L)', 'qty' => 2]]],
            ['daysAgo' => 10, 'hour' => 21, 'type' => 'dine_in',  'method' => 'card',     'items' => [['dish' => 'Bife de Chorizo Angus Madurado (400g)', 'qty' => 3], ['dish' => 'Provoleta Ahumada al Orégano', 'qty' => 2], ['dish' => 'Volcán de Chocolate 70% con Helado', 'qty' => 2]]],
            ['daysAgo' => 9,  'hour' => 14, 'type' => 'dine_in',  'method' => 'card',     'items' => [['dish' => 'Picaña Prime a la Espada (350g)', 'qty' => 4], ['dish' => 'Guacamole Martajado en Molcajete', 'qty' => 2]]],
            ['daysAgo' => 8,  'hour' => 19, 'type' => 'takeaway', 'method' => 'cash',     'items' => [['dish' => 'Costillar de Cerdo BBQ al Ahumador (500g)', 'qty' => 3], ['dish' => 'Papas Rústicas al Romero y Parmesano', 'qty' => 3]]],
            ['daysAgo' => 7,  'hour' => 15, 'type' => 'dine_in',  'method' => 'card',     'items' => [['dish' => 'Banquete Ejecutivo VIP + Malbec (4-6 Personas)', 'qty' => 1], ['dish' => 'Tuétanos Rostizados al Carbón', 'qty' => 2]]],
            ['daysAgo' => 6,  'hour' => 14, 'type' => 'dine_in',  'method' => 'card',     'items' => [['dish' => 'Arrachera Las Brazas (300g)', 'qty' => 4], ['dish' => 'Chorizo Argentino a la Parrilla (Par)', 'qty' => 2]]],
            ['daysAgo' => 5,  'hour' => 20, 'type' => 'delivery', 'method' => 'transfer', 'items' => [['dish' => 'Tabla Gaucha Mixta (2 Personas)', 'qty' => 2], ['dish' => 'Cerveza Artesanal IPA / Roja de la Casa', 'qty' => 4]]],
            ['daysAgo' => 4,  'hour' => 13, 'type' => 'dine_in',  'method' => 'card',     'items' => [['dish' => 'Ojo de Bife / Ribeye High Choice (400g)', 'qty' => 3], ['dish' => 'Espárragos Verdes a la Parrilla', 'qty' => 2], ['dish' => 'Panqueque de Dulce de Leche Flameado', 'qty' => 2]]],
            ['daysAgo' => 3,  'hour' => 21, 'type' => 'dine_in',  'method' => 'cash',     'items' => [['dish' => 'Tomahawk Steak Flameado en Mesa (900g)', 'qty' => 2], ['dish' => 'Botella Vino Malbec Reserva Mendoza', 'qty' => 2], ['dish' => 'Queso Fundido con Chistorra', 'qty' => 2]]],
            ['daysAgo' => 2,  'hour' => 14, 'type' => 'dine_in',  'method' => 'card',     'items' => [['dish' => 'Picaña Prime a la Espada (350g)', 'qty' => 5], ['dish' => 'Yuca Frita Crocante a la Huancaína', 'qty' => 3], ['dish' => 'Limonada Frappé de Hierbabuena (Jarra 1L)', 'qty' => 2]]],
            ['daysAgo' => 2,  'hour' => 20, 'type' => 'delivery', 'method' => 'transfer', 'items' => [['dish' => 'Asado de Tira Cocción Lenta (450g)', 'qty' => 3], ['dish' => 'Empanadas Cortadas a Cuchillo (3 pzas)', 'qty' => 2]]],
            ['daysAgo' => 1,  'hour' => 13, 'type' => 'dine_in',  'method' => 'card',     'items' => [['dish' => 'Parrillada Las Brazas Imperial (4 Personas)', 'qty' => 2], ['dish' => 'Botella Vino Malbec Reserva Mendoza', 'qty' => 1], ['dish' => 'Volcán de Chocolate 70% con Helado', 'qty' => 3]]],
            ['daysAgo' => 1,  'hour' => 20, 'type' => 'dine_in',  'method' => 'cash',     'items' => [['dish' => 'Bife de Chorizo Angus Madurado (400g)', 'qty' => 4], ['dish' => 'Guacamole Martajado en Molcajete', 'qty' => 2], ['dish' => 'Cerveza Artesanal IPA / Roja de la Casa', 'qty' => 4]]],
            // Ventas de HOY (diferentes horas para que el gráfico de hoy por hora se vea genial)
            ['daysAgo' => 0,  'hour' => 12, 'type' => 'dine_in',  'method' => 'card',     'items' => [['dish' => 'Arrachera Las Brazas (300g)', 'qty' => 3], ['dish' => 'Queso Fundido con Chistorra', 'qty' => 2], ['dish' => 'Limonada Frappé de Hierbabuena (Jarra 1L)', 'qty' => 2]]],
            ['daysAgo' => 0,  'hour' => 13, 'type' => 'dine_in',  'method' => 'transfer', 'items' => [['dish' => 'Tomahawk Steak Flameado en Mesa (900g)', 'qty' => 1], ['dish' => 'Picaña Prime a la Espada (350g)', 'qty' => 2], ['dish' => 'Botella Vino Malbec Reserva Mendoza', 'qty' => 1]]],
            ['daysAgo' => 0,  'hour' => 14, 'type' => 'delivery', 'method' => 'card',     'items' => [['dish' => 'Hamburguesa Wagyu Smokehouse (250g)', 'qty' => 3], ['dish' => 'Choripán Gaucho con Chimichurri', 'qty' => 2], ['dish' => 'Papas Rústicas al Romero y Parmesano', 'qty' => 2]]],
            ['daysAgo' => 0,  'hour' => 15, 'type' => 'takeaway', 'method' => 'cash',     'items' => [['dish' => 'Tabla Gaucha Mixta (2 Personas)', 'qty' => 1], ['dish' => 'Flan Napolitano al Caramelo de Ron', 'qty' => 2]]],
        ];

        $customerList = array_values($customers);
        $tableList    = array_values($tables);

        foreach ($historicalSchedule as $idx => $entry) {
            $txRef = "HIST-SEED-2026-{$idx}";
            $orderDate = now()->subDays($entry['daysAgo'])->setHour($entry['hour'])->setMinute(25)->setSecond(0);

            // Calcular total real según los platillos
            $orderTotal = 0;
            foreach ($entry['items'] as $itemData) {
                $dishObj = $dishes[$itemData['dish']] ?? null;
                if ($dishObj) {
                    $orderTotal += ((float) $dishObj->price) * $itemData['qty'];
                }
            }

            $existingPayment = Payment::where('transaction_ref', $txRef)->first();
            $histCustomer    = $customerList[$idx % count($customerList)];
            $histTable       = $entry['type'] === 'dine_in' ? $tableList[$idx % count($tableList)] : null;

            if ($existingPayment) {
                $histOrder = $existingPayment->order;
                if ($histOrder) {
                    $histOrder->update([
                        'subtotal'     => $orderTotal,
                        'total_amount' => $orderTotal,
                        'created_at'   => $orderDate,
                        'updated_at'   => $orderDate,
                    ]);
                }
            } else {
                $histOrder = Order::create([
                    'table_id'         => $histTable?->id,
                    'user_id'          => $idx % 2 === 0 ? $waiter1->id : $waiter2->id,
                    'customer_id'      => $histCustomer->id,
                    'driver_id'        => $entry['type'] === 'delivery' ? $driver1->id : null,
                    'order_type'       => $entry['type'],
                    'status'           => 'paid',
                    'subtotal'         => $orderTotal,
                    'tax'              => 0,
                    'total_amount'     => $orderTotal,
                    'delivery_address' => $entry['type'] === 'delivery' ? 'Av. Equipetrol Norte #210 (Entrega completada)' : null,
                    'delivered_at'     => $entry['type'] === 'delivery' ? $orderDate->copy()->addMinutes(35) : null,
                    'created_at'       => $orderDate,
                    'updated_at'       => $orderDate,
                ]);

                foreach ($entry['items'] as $itemData) {
                    $dishObj = $dishes[$itemData['dish']] ?? null;
                    if ($dishObj) {
                        OrderItem::create([
                            'order_id'   => $histOrder->id,
                            'dish_id'    => $dishObj->id,
                            'quantity'   => $itemData['qty'],
                            'unit_price' => $dishObj->price,
                            'subtotal'   => ((float) $dishObj->price) * $itemData['qty'],
                            'notes'      => 'Servicio completado',
                            'status'     => 'served',
                            'created_at' => $orderDate,
                            'updated_at' => $orderDate,
                        ]);
                    }
                }

                Payment::create([
                    'order_id'        => $histOrder->id,
                    'payment_method'  => $entry['method'],
                    'amount'          => $orderTotal,
                    'transaction_ref' => $txRef,
                    'paid_at'         => $orderDate->copy()->addMinutes(45),
                    'created_at'      => $orderDate->copy()->addMinutes(45),
                    'updated_at'      => $orderDate->copy()->addMinutes(45),
                ]);
            }
        }
    }
}
