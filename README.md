# 🌌 Aurora CRM - Gestión de Leads Multiorigen

Aurora es un CRM inteligente desarrollado con **Laravel 12** y **Filament v4**. Su objetivo principal es centralizar la recepción de leads provenientes de diversas plataformas externas (WordPress, Landings, etc.), garantizando la integridad de los datos y evitando la duplicidad de clientes mediante un sistema de validación por email.

## 🚀 Estado del Proyecto: Fase 1 (Arquitectura de Datos)

Actualmente, el proyecto se encuentra en la fase de definición de modelos y recursos básicos.

### Pasos realizados hasta ahora:
1.  **Instalación:** `composer create-project laravel/laravel:^12.0`
2.  **Entorno:** Configuración de `.env` y generación de `key`.
3.  **Base de Datos:** Configuración en Laragon y ejecución de migraciones iniciales.
4.  **Filament v4:** Instalación del núcleo y creación del panel administrativo.
5.  **Seguridad:** Creación del usuario administrador principal.
6.  **Control de versiones:** Repositorio inicializado en GitHub.

---

## 🛠️ Stack Tecnológico
* **Framework:** Laravel 12
* **Panel Administrativo:** Filament v4
* **Entorno Local:** Laragon / PHP 8.4+
* **Base de Datos:** MySQL / MariaDB

---

## 📐 Arquitectura de Datos (Próximamente)

El sistema se basa en 4 pilares fundamentales para evitar la redundancia:

1.  **Sources (Orígenes):** Registro de las webs que envían datos. Cada una cuenta con un `api_token` único.
2.  **Customers (Clientes):** Directorio único basado en el `email`. Un cliente es único en el sistema, independientemente de cuántas veces contacte.
3.  **Services (Servicios):** Catálogo de productos o clases ofrecidas.
4.  **Interactions (Leads):** Registro histórico que une a un Cliente con un Origen y un Servicio específico.
5.  **Service_source:** Tabla pivote para conectar servicios y sources. 

---

## 🔧 Instalación y Puesta en Marcha

Si acabas de clonar el repositorio, sigue estos pasos:

1. **Instalar dependencias:**
   ```bash
   composer install
   npm install && npm run dev

2. **Configurar el entorno:**
   Copia .env.example a .env.
   Configura tus credenciales de base de datos.

3. **Generar claves y migrar:**
   php artisan key:generate
   php artisan migrate

4. **Acceso al Panel:**
   URL: /admin
   Crear usuario (si no existe): php artisan make:filament-user

# [ 📝 BACKLOG ]
## 🦴 Fase: Estructura de recursos Filament. Configuración API 
- [x] Creación y configuración de Migraciones para Sources, Customers, Services y Service_source.

- [x] Implementación de la tabla de Interacciones (Leads)(lanzar las migraciones).

- [x] Generación de Recursos en Filament con --generate.

- [x] Creación y configuración de formularios de cada recurso.

- [x] Instalación php artisan install api

- [x] Desarrollo de la API de recepción de datos con lógica de "No Duplicidad".

## 🔔 Fase: Sistema de Notificaciones y Alertas

Sistema de avisos en tiempo real para la gestión inmediata de leads entrantes.

- [x] **Configuración de Database Notifications:** Habilitar el sistema de persistencia de notificaciones de Laravel.
- [x] **Trigger en LeadController:** Implementar el envío de notificaciones internas al recibir un lead exitoso por la API.
- [x] **Notificaciones en Panel:** Configurar el componente `DatabaseNotifications` de Filament para mostrar la "campanita" en el Header.
- [x] **Alertas de Error:** Implementar avisos específicos para tokens inválidos o fuentes inactivas.

📋 Hoja de Ruta: Funcionalidades Core de Aurora
- [x] **1. Vista de Consulta (Infolist)**
Es: Una pantalla intermedia entre la lista y el formulario de edición.

Funcion: Permite visualizar todos los datos del lead, notas y servicios contratados en un formato de "tarjetas" limpio, evitando modificar datos por error.

Pasos: Usaremos la clase Infolist de Filament para organizar los campos de forma elegante.

- [ ] **2. Gestión de Catálogo y Precios**
Es: Un nuevo módulo de Servicios.

Funcion: Para que cada web (Source) pueda tener sus propios servicios con descripción y precio.

Pasos: Crearemos la tabla services vinculada a sources. Esto permitirá que, al entrar un lead de "Web A", solo puedas elegir servicios de esa web.

- [ ] **3. Módulo de Reservas y Sincronización con Google Calendar**
Es: El corazón del CRM. Una sección de Bookings (Citas).

Funcion: Agendar el día y la hora de la prestación del servicio. Al guardar, se crea automáticamente el evento en tu Google Calendar y se le envía la invitación al correo del cliente.

Pasos: Usaremos la API de Google y un "Observer" en Laravel que detecte cuando se crea una reserva para disparar la sincronización.

- [ ] **4. Acción de "WhatsApp Rápido"**
Es: Un botón dinámico en la ficha del lead.

Funcion: Iniciar una conversación con un solo clic. Al pulsarlo, abre WhatsApp con el número del cliente y un mensaje como: "Hola [Nombre], te contacto desde [Web] por tu consulta sobre [Servicio]...".

Pasos: Un botón de acción que construye una URL dinámica: https://wa.me/telefono?text=mensaje_codificado.

- [ ] **5. Centro de Mensajería y Automatización de Estados**
Es: Un historial de mensajes enviados desde Aurora.

Funcion: Para que todo el equipo sepa qué se le ha dicho al cliente. Además, cuando envíes el primer mensaje, Aurora cambiará el estado a "Contactado" automáticamente. Al crear la reserva, el estado pasará a "Reservado".

Pasos: Una tabla messages y lógica de eventos en Laravel que actualice el campo status de la interacción según la actividad.

- [ ] **6. Sistema de Recordatorios de Seguimiento**
Es: Alertas basadas en fechas.

Funcion: Para que no se te "enfríe" ningún lead. Si un lead no ha pasado a reserva en 48h, aparecerá en una sección de "Pendientes de seguimiento".

Pasos: Un filtro en el Dashboard que resalte las interacciones cuya updated_at sea antigua y sigan en estado "Contactado".