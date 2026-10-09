<?php

return [
    // Claves: ID de Source. Sin configuración explícita solo se permite blocking.
    // Ejemplo: 4 => ['blocking', 'non_blocking'],
    'source_availability_policies' => [
        4 => ['blocking', 'non_blocking'], // pruebas_web (Aurora Connector).
    ],

    'default_slot_interval_minutes' => (int) env('BOOKING_SLOT_INTERVAL_MINUTES', 30),

    'allowed_slot_intervals' => [
        15,
        20,
        30,
        45,
        60,
        90,
    ],
];
