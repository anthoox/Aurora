# Concurrencia de reservas en MySQL

Desde la raíz del proyecto:

```bash
php tests/Concurrency/mysql-bookings.php
```

Requiere MySQL y permisos para crear bases de datos con las credenciales locales.
El script crea una base vacía con nombre `aurora_concurrency_test_<fecha>_<sufijo>`
y ejecuta allí las migraciones. No selecciona ni modifica `aurora_db`.
No utiliza `RefreshDatabase` ni cambia `.env`.

Ejecuta cinco repeticiones de seis escenarios, con dos procesos PHP,
conexiones MySQL independientes y fuentes diferentes en cada pareja:

- Duplicado del mismo cliente, servicio y horario: 201 y 422 `booking_already_exists`.
- Clientes distintos y política `blocking`: 201 y 422 `booking_slot_unavailable`.
- Email nuevo compartido por servicios distintos: dos respuestas 201 y un cliente.
- Clientes distintos y política `non_blocking`: dos respuestas 201 en la misma franja.
- Dos confirmaciones de pendientes `non_blocking`: solo una se confirma; la otra permanece pendiente.
- Confirmación frente a creación `blocking`: solo una operación consigue ocupar el intervalo.

Para ejecutar solamente los dos escenarios de confirmación:

```bash
php tests/Concurrency/mysql-bookings.php --phase3
```

Las confirmaciones invocan directamente `BookingScheduler`, sin añadir rutas
públicas. Su resultado se normaliza en el informe como éxito 201 o validación 422;
esos códigos de confirmación son del harness, no de un endpoint nuevo.
Google Calendar se sustituye por un mock en los trabajadores.
En la carrera de confirmación frente a creación se alterna el proceso favorecido
para comprobar ambos órdenes de adquisición del bloqueo.

Las peticiones recorren el kernel HTTP de Laravel. Una barrera sincroniza su
inicio y un listener de consultas prolonga 300 ms el bloqueo del servicio.
Para el email nuevo, otra barrera obliga a ambos procesos a comprobar su
ausencia antes de continuar. No se modifica el scheduler para instrumentarlo.

Los resultados incluyen códigos HTTP, cuerpos JSON, conteos, identificadores
de conexión y tiempos de adquisición del bloqueo. El proceso devuelve código 1
si falla una comprobación. Los archivos de sincronización y `report.json` se
guardan en el directorio temporal del sistema, cuya ruta se imprime al terminar.
La base de pruebas se conserva para inspección; cada ejecución crea otra nueva.

Estas pruebas comprueban creación desde la API y confirmación mediante el servicio
con aislamiento MySQL local. No prueban transporte HTTP por red, WordPress, carga
sostenida ni todos los posibles intercalados de transacciones. La base aislada
comparte servidor MySQL con el entorno local.
