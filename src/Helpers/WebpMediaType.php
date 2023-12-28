<?php

namespace PixlMint\Media\Helpers;

class WebpMediaType extends RasterImageMediaType
{
    public static function getMimeType(): string
    {
        return "image/webp";
    }
}