# 🗄️ Diseño de Base de Datos - Sistema de Restaurante "Las Brazas"

Este documento contiene el diseño de la base de datos relacional para el sistema de gestión del restaurante. Incluye el **Diagrama de Clases (UML / Entidad-Relación)** en Mermaid y el detalle de cada módulo.

---

## 📊 Diagrama de Clases (Mermaid)

```mermaid
classDiagram
    %% Módulo de Usuarios y Roles
    class Role {
        +int id
        +string name
        +string description
    }

    class User {
        +int id
        +int role_id
        +string name
        +string email
        +string password
        +string phone
        +boolean is_active
        +datetime created_at
    }

    %% Módulo de Mesas, Áreas y Clientes
    class Area {
        +int id
        +string name
        +string description
    }

    class Table {
        +int id
        +int area_id
        +string table_number
        +int capacity
        +string status
    }

    class Customer {
        +int id
        +string name
        +string phone
        +string email
        +string preferences_notes
    }

    class Reservation {
        +int id
        +int customer_id
        +int table_id
        +int user_id
        +datetime reservation_date
        +int guest_count
        +string status
        +string special_requests
    }

    %% Módulo de Menú y Recetas
    class Category {
        +int id
        +string name
        +string description
        +boolean is_active
    }

    class Dish {
        +int id
        +int category_id
        +string name
        +string description
        +decimal price
        +string image_url
        +int prep_time_minutes
        +boolean is_available
    }

    class Recipe {
        +int id
        +int dish_id
        +text instructions
    }

    class RecipeIngredient {
        +int id
        +int recipe_id
        +int ingredient_id
        +decimal quantity_required
        +string unit_of_measure
    }

    %% Módulo de Inventario y Proveedores
    class Supplier {
        +int id
        +string company_name
        +string contact_name
        +string phone
        +string email
    }

    class Ingredient {
        +int id
        +int supplier_id
        +string name
        +string unit_of_measure
        +decimal current_stock
        +decimal minimum_stock
        +decimal cost_per_unit
    }

    class InventoryMovement {
        +int id
        +int ingredient_id
        +int user_id
        +string movement_type
        +decimal quantity
        +string reason
        +datetime created_at
    }

    %% Módulo de Comandas / Pedidos y Pagos
    class Order {
        +int id
        +int table_id
        +int user_id
        +int customer_id
        +string order_type
        +string status
        +decimal total_amount
        +datetime created_at
    }

    class OrderItem {
        +int id
        +int order_id
        +int dish_id
        +int quantity
        +decimal unit_price
        +decimal subtotal
        +string notes
        +string status
    }

    class Payment {
        +int id
        +int order_id
        +string payment_method
        +decimal amount
        +string transaction_ref
        +datetime paid_at
    }

    %% Relaciones
    Role "1" -- "*" User : posee
    Area "1" -- "*" Table : contiene
    Customer "1" -- "*" Reservation : realiza
    Table "1" -- "*" Reservation : asignada_a
    User "1" -- "*" Reservation : registra

    Category "1" -- "*" Dish : clasifica
    Dish "1" -- "1" Recipe : contiene
    Recipe "1" -- "*" RecipeIngredient : esta_compuesta_por
    Ingredient "1" -- "*" RecipeIngredient : usado_en

    Supplier "1" -- "*" Ingredient : suministra
    Ingredient "1" -- "*" InventoryMovement : registra
    User "1" -- "*" InventoryMovement : autoriza

    Table "1" -- "*" Order : atiende
    User "1" -- "*" Order : toma_pedido
    Customer "1" -- "*" Order : consume
    Order "1" -- "*" OrderItem : contiene
    Dish "1" -- "*" OrderItem : ordenado_en
    Order "1" -- "*" Payment : pagado_con
```

---

## 📑 Detalle de Módulos y Tablas

### 1. 👥 Módulo de Usuarios y Roles (`users`, `roles`)
* **`roles`**: Maneja los niveles de acceso del sistema:
  * `Administrador`: Acceso total (reportes, usuarios, inventario, precios).
  * `Chef / Cocina`: Vista de recetas, comandas/pedidos pendientes e inventario.
  * `Mesero`: Toma de pedidos, gestión de mesas y estado de comandas.
  * `Recepcionista`: Gestión del mapa de mesas y reservas de clientes.
  * `Cliente`: (Opcional) Para realizar reservas web o consultar el menú digital.

### 2. 🪑 Módulo de Salón, Mesas y Reservas (`areas`, `tables`, `customers`, `reservations`)
* **`areas`**: Zonas del restaurante (ej: Terraza, Salón Principal, Zona VIP, Bar).
* **`tables`**: Representa las mesas físicas del restaurante:
  * `capacity`: Número de sillas/comensales por mesa.
  * `status`: `disponible`, `reservada`, `ocupada`, `mantenimiento`.
* **`reservations`**: Controla las citas de comensales:
  * Permite asociar la reserva a un cliente, fecha/hora, número de personas, mesa asignada y requerimientos especiales (ej: silla de bebé, cumpleaños).

### 3. 🍲 Módulo de Menú y Platillos (`categories`, `dishes`)
* **`categories`**: Entradas, Platos Fuertes, Bebidas, Postres, Menú Infantil, etc.
* **`dishes`**: Lista de productos que se venden al cliente (precio, foto, tiempo estimado de preparación, disponibilidad).

### 4. 📦 Módulo de Inventario e Insumos (`suppliers`, `ingredients`, `inventory_movements`)
* **`suppliers`**: Registro de proveedores que surten materia prima.
* **`ingredients`**: Ingredientes e insumos almacenados (ej: Carne Arrachera, Sal, Aceite, Cerveza):
  * `unit_of_measure`: Kilogramos, Gramos, Litros, Mililitros, Unidades.
  * `minimum_stock`: Dispara alertas automáticas de bajo stock cuando el inventario cae por debajo de este límite.
* **`inventory_movements`**: Historial de entradas (compras a proveedores), salidas (descuento automático al vender un platillo), mermas o despilfarros y ajustes manuales.

### 5. 📜 Módulo de Recetas (`recipes`, `recipe_ingredients`)
* Relaciona **Platillos** con **Ingredientes**:
  * Ejemplo: 1 Platillo *"Tacos Arrachera"* requiere `250g` de Arrachera, `3` Tortillas, `50g` de Queso.
  * **Descuento Automático**: Al marcar un pedido como preparado/vendido, el sistema descuenta automáticamente las proporciones de ingredientes del inventario.

### 6. 💳 Módulo de Pedidos / Comandas y Pagos (`orders`, `order_items`, `payments`) *(Módulo Adicional Recomendado)*
* **`orders`**: Representa la cuenta o comanda de una mesa.
* **`order_items`**: Detalle de los platillos solicitados, cantidad, especificaciones de cocina (ej: "Sin cebolla", "Término medio") y su estado en cocina (`pendiente`, `en_preparación`, `listo`, `servido`).
* **`payments`**: Registro de pagos recibidos (efectivo, tarjeta, transferencia).

---

##💡 Sugerencias Adicionales Recomendadas

> [!TIP]
> 1. **Pantalla de Cocina (KDS - Kitchen Display System)**: Muestra en tiempo real los `order_items` ordenados por antigüedad para que los cocineros marquen lo que ya prepararon.
> 2. **Alerta de Mermas / Desperdicio**: Permitir al chef registrar si un ingrediente se dañó para no distorsionar el stock real.
> 3. **Unión de Mesas**: Agregar soporte para unir dos o más mesas en una sola reserva/pedido cuando acude un grupo grande.
