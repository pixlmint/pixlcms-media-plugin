<?php

namespace PixlMint\Media\Hooks;

use Nacho\Contracts\Hooks\PreFindRoute;
use PixlMint\Media\Controllers\MediaController;

class MediaGetHook implements PreFindRoute
{
    public function call(array $routes, string $requestUrl): array
    {
        if (str_starts_with($requestUrl, '/media')) {
            $routes[] = [
                'route' => $requestUrl,
                'controller' => MediaController::class,
                'function' => 'getMedia',
            ];
        }

        return $routes;
    }
}
