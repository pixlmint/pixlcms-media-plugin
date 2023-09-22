<?php

use PixlMint\Media\Controllers\MediaController;

return [
    'routes' => [
        [
            'route' => '/api/admin/gallery/upload',
            'controller' => MediaController::class,
            'function' => 'uploadMedia',
        ],
        [
            'route' => '/api/admin/gallery/load',
            'controller' => MediaController::class,
            'function' => 'loadMediaForEntry',
        ],
        [
            'route' => '/api/admin/media/delete',
            'controller' => MediaController::class,
            'function' => 'deleteMedia',
        ],
    ]
];