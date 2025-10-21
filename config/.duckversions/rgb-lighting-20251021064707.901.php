<?php

return [
    'async_concurrency_driver' => 'fork', //sync, process
    'devices' => [
        'solo' => [
            'shape' => 'single-diode',
            'device_path' => '/dev/spidev0.0',
            'neopixel_type' => 'rgb',
        ]
    ],
];
