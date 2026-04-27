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
- [ ] **Alertas de Error:** Implementar avisos específicos para tokens inválidos o fuentes inactivas.
- [ ] **(Extra) Sonido de Alerta:** Añadir un trigger sonoro opcional al recibir un nuevo registro.