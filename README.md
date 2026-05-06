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
- [x] **1. Vista de Consulta (Infolist)** Es: Una pantalla intermedia entre la lista y el formulario de edición. Funcion: Permite visualizar todos los datos del lead, notas y servicios contratados en un formato de "tarjetas" limpio, evitando modificar datos por error.


# 🧩 FASE 1 — Consolidar el CRM Base

Objetivo:
Construir un CRM sólido, rápido y cómodo antes de añadir automatizaciones avanzadas.

---

## 1. Filtros avanzados de leads

### Objetivo
Facilitar la gestión de grandes cantidades de leads.

### Funcionalidades
- [ ] Filtrar por estado
- [ ] Filtrar por web/source
- [ ] Filtrar por servicio
- [ ] Filtrar por fecha
- [ ] Filtrar leads sin contactar
- [ ] Filtrar leads vendidos
- [ ] Filtrar leads descartados
- [ ] Búsqueda avanzada de clientes

### Aprendizajes
- Filtros de Filament
- Query Builder
- Optimización de tablas

---

## 2. Vista completa del cliente

### Objetivo
Centralizar toda la información del cliente en una sola vista.

### Funcionalidades
- [ ] Ver datos personales
- [ ] Ver historial de leads
- [ ] Ver servicios solicitados
- [ ] Ver reservas futuras
- [ ] Ver notas internas
- [ ] Ver historial comercial
- [ ] Ver enlace a editar desde la vista de cliente y botón de volver a vista del cliente


### Aprendizajes
- Relaciones complejas
- Infolists avanzados
- Arquitectura CRM

---

## 3. Sistema de detección de duplicados

### Objetivo
Evitar clientes duplicados.

### Funcionalidades
- [ ] Detectar email existente
- [ ] Detectar teléfono existente
- [ ] Asociar nuevas interacciones al mismo cliente
- [ ] Mostrar aviso de cliente recurrente

### Aprendizajes
- Lógica de negocio
- Validaciones
- Reutilización de modelos

---

## 4. Historial de interacciones (Timeline)

### Objetivo
Tener trazabilidad completa de acciones realizadas sobre un lead.

### Funcionalidades
- [ ] Registrar cambios de estado
- [ ] Registrar notas internas
- [ ] Registrar creación de reservas
- [ ] Mostrar timeline cronológico

### Aprendizajes
- Eventos y observers
- Activity logs
- Diseño de timeline

---

## 5. Acción rápida de WhatsApp

### Objetivo
Contactar clientes rápidamente desde el CRM.

### Funcionalidades
- [ ] Botón de WhatsApp en leads
- [ ] Mensaje automático dinámico
- [ ] Datos dinámicos:
  - Nombre
  - Servicio
  - Web/source

### Aprendizajes
- URL dinámicas
- UX rápida
- Acciones de Filament

---

# 📅 FASE 2 — Reservas y Operativa

Objetivo:
Convertir Aurora en una herramienta real de gestión de servicios y citas.

---

## 6. Gestión de catálogo y precios

### Objetivo
Permitir que cada web tenga servicios personalizados.

### Funcionalidades
- [ ] Servicios asociados a cada Source
- [ ] Precios personalizados
- [ ] Descripciones
- [ ] Filtrado automático por web

### Aprendizajes
- Relaciones many-to-many
- Arquitectura modular
- Formularios dinámicos

---

## 7. Módulo de reservas (Bookings)

### Objetivo
Gestionar citas y reservas desde el CRM.

### Funcionalidades
- [ ] Crear reservas
- [ ] Fecha y hora
- [ ] Estado de reserva:
  - Pendiente
  - Confirmada
  - Cancelada
  - Realizada

### Aprendizajes
- Diseño de flujos
- Nuevos recursos Filament
- Estados comerciales

---

## 8. Integración con Google Calendar

### Objetivo
Sincronizar automáticamente las reservas.

### Funcionalidades
- [ ] Crear evento en Google Calendar
- [ ] Enviar invitación al cliente
- [ ] Sincronización automática
- [ ] Actualización de eventos

### Aprendizajes
- APIs externas
- OAuth
- Laravel Services
- Observers

---

# 🤖 FASE 3 — Automatización Comercial

Objetivo:
Automatizar tareas repetitivas y mejorar seguimiento comercial.

---

## 9. Automatización de estados

### Objetivo
Actualizar automáticamente estados del lead.

### Funcionalidades
- [ ] Primer contacto → Contactado
- [ ] Reserva creada → Reservado
- [ ] Reserva finalizada → Convertido

### Aprendizajes
- Eventos
- Automatización de negocio
- Lógica desacoplada

---

## 10. Centro de mensajería

### Objetivo
Centralizar mensajes enviados al cliente.

### Funcionalidades
- [ ] Historial de WhatsApps
- [ ] Historial de emails
- [ ] Notas internas
- [ ] Registro de actividad

### Aprendizajes
- Arquitectura de comunicaciones
- Historial persistente
- Timeline avanzado

---

## 11. Sistema de seguimiento automático

### Objetivo
Evitar perder leads.

### Funcionalidades
- [ ] Leads pendientes de seguimiento
- [ ] Leads sin respuesta en X horas
- [ ] Alertas internas
- [ ] Dashboard comercial

### Aprendizajes
- Jobs
- Scheduler
- Automatizaciones Laravel

---

# 📊 FASE 4 — Métricas y Negocio

Objetivo:
Convertir Aurora en una herramienta de análisis comercial.

---

## 12. Dashboard avanzado

### Métricas
- [ ] Leads mensuales
- [ ] Conversión por web
- [ ] Conversión por servicio
- [ ] Leads vendidos
- [ ] Leads pendientes
- [ ] Tiempo medio de contacto
- [ ] Servicios más vendidos
- [ ] Webs más rentables

### Aprendizajes
- Analytics
- KPIs
- Consultas complejas

---

## 13. Gestión comercial y ventas

### Objetivo
Controlar presupuestos y ventas.

### Funcionalidades
- [ ] Precio estimado
- [ ] Precio final
- [ ] Forma de pago
- [ ] Estado comercial
- [ ] Observaciones de venta

### Aprendizajes
- Flujo comercial
- Estados financieros
- CRM avanzado

---

# 👥 FASE 5 — Escalado

Objetivo:
Preparar Aurora para equipos.

---

## 14. Usuarios y roles

### Roles
- [ ] Administrador
- [ ] Comercial/Gestor
- [ ] Solo lectura

### Funcionalidades
- [ ] Restricción de permisos
- [ ] Acceso por módulos
- [ ] Gestión de usuarios

### Aprendizajes
- Policies
- Permissions
- Multiusuario

---

# 🔮 Ideas futuras

- [ ] Emails automáticos
- [ ] Pipeline comercial tipo Kanban
- [ ] Integración con Telegram
- [ ] Integración con Stripe
- [ ] Exportación PDF/Excel
- [ ] Facturación
- [ ] Webhooks externos
- [ ] API pública
- [ ] Sistema multiempresa
- [ ] Aplicación móvil

---

# 🛠 Stack tecnológico

- Laravel 12
- PHP 8.3
- Filament PHP v4
- MySQL
- TailwindCSS
- Livewire
- Alpine.js

---

# 🧠 Filosofía del proyecto

Aurora busca ser:

- Simple de usar
- Rápido para el negocio
- Escalable
- Automatizable
- Modular
- Profesional