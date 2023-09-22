<?php

use PixlMint\Media\Controllers\MediaController;

return [
    'routes' => [
        [
            'route' => '/api/admin/entry/gallery/upload',
            'controller' => MediaController::class,
            'function' => 'uploadMedia',
        ],
        [
            'route' => '/api/admin/entry/media/load',
            'controller' => MediaController::class,
            'function' => 'loadMediaForEntry',
        ],
        [
            'route' => '/api/admin/entry/media/delete',
            'controller' => MediaController::class,
            'function' => 'deleteMedia',
        ],
    ]
];