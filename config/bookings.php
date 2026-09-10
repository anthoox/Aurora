<?php

return [
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
