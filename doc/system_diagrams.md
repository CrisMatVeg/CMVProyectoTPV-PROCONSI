# Diagramas de Arquitectura y Funcionamiento: Electrobazar TPV

Este documento presenta una visión técnica y funcional de la aplicación TPV mediante diagramas que detallan su estructura, flujo de navegación y capacidades de usuario.

---

## 1. Diagrama de Casos de Uso
Define las interacciones de los diferentes tipos de usuarios con el sistema.

```mermaid
graph LR
    subgraph Actores
        C[Cajero]
        A[Administrador]
    end

    subgraph "Sistema Electrobazar TPV"
        UC1((Iniciar Sesión))
        UC2((Realizar Venta TPV))
        UC3((Cierre de Caja))
        UC4((Consultar Historial))
        UC5((Gestionar Productos))
        UC6((Gestionar Usuarios))
        UC7((Dashboard Admin))
        UC8((Gestionar Perfil))
    end

    C --> UC1
    C --> UC2
    C --> UC3
    C --> UC8

    A --- C
    A --> UC4
    A --> UC5
    A --> UC6
    A --> UC7
```

---

## 2. Diagrama de Navegación
Muestra el flujo de pantallas y cómo el Dashboard actúa como concentrador según el rol.

```mermaid
graph TD
    Start((Inicio)) --> IP[Página Pública]
    IP --> Login[Página de Acceso]
    Login -- "Credenciales OK" --> Dash{Dashboard}
    
    Dash -- "Todos" --> TPV[TPV - Ventas]
    Dash -- "Todos" --> Perfil[Mi Perfil]
    
    subgraph "Administración (Solo Admin)"
        Dash -- "irUsuarios" --> Usuarios[Gestión de Personal]
        Dash -- "irProductos" --> Productos[Gestión de Productos]
        Dash -- "irHistorial" --> Historial[Historial de Ventas]
    end
    
    TPV -- "Cerrar Caja" --> CC[Cierre de Caja]
    CC --> IP
    
    Perfil --> Dash
    Usuarios --> Dash
    Productos --> Dash
    Historial --> Dash
    
    Dash -- "Salir" --> IP
```

---

## 3. Arquitectura MVC (Model-View-Controller)
Detalla cómo se procesa una petición desde que el usuario interactúa hasta que se muestra la respuesta.

```mermaid
sequenceDiagram
    participant U as Usuario
    participant I as index.php
    participant C as Controller (PHP)
    participant M as Model (PDO)
    participant DB as Base de Datos
    participant V as View (HTML/CSS/JS)

    U->>I: Petición (POST/GET)
    I->>C: Carga el controlador correspondiente
    C->>M: Solicita datos o ejecuta acción
    M->>DB: Consulta SQL (PDO)
    DB-->>M: Resultados (Fetch)
    M-->>C: Retorna objetos/arrays
    C->>V: Inyecta datos en $avInicioPrivado, etc.
    V-->>U: Muestra página renderizada (layout.php)
```

---

## 4. Estructura de Datos (Modelos Completos)
Estructura técnica de las clases del sistema.

```mermaid
classDiagram
    class Usuario {
        -int id
        -string nombreCompleto
        -string username
        -string password
        -string rol
        -bool activo
        +__construct(id, nombre, user, pass, rol, activo)
        +getId() int
        +getNombreCompleto() string
        +getUsername() string
        +getRol() string
        +getActivo() bool
        +setNombreCompleto(nombre)
        +setRol(rol)
    }

    class Producto {
        -int id
        -string nombre
        -string codigo
        -float precio
        -string icono
        -string categoria
        -bool activo
        -int stock
        +__construct(id, nombre, cod, pr, icon, cat, act, st)
        +getId() int
        +getNombre() string
        +getCodigo() string
        +getPrecio() float
        +getStock() int
        +getActivo() bool
    }

    class Venta {
        -int id
        -int numeroTicket
        -string tipoCliente
        -string nombreCliente
        -string nifCliente
        -string metodoPago
        -float subtotal
        -float descuentoPct
        -float total
        -int idCajero
        -string creadoEn
        -array lineas
        +__construct(id, ticket, tipo, ...)
        +getTotal() float
        +getLineas() array
        +getNumeroTicket() int
    }

    class LineaVenta {
        -int id
        -int id_venta
        -int id_producto
        -string nombre_producto
        -float precio_unitario
        -int cantidad
        -float total_linea
    }

    Venta "1" -- "*" LineaVenta : contiene
    Usuario "1" -- "*" Venta : registra
    LineaVenta "*" -- "1" Producto : referencia
```
