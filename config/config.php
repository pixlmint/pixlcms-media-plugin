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
            'route' => '/api/admin/gallery/upload-b64',
            'controller' => MediaController::class,
            'function' => 'uploadBase64',
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