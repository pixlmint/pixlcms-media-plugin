<?php

namespace PixlMint\Media\Helpers;

class JpegMediaType extends RasterImageMediaType
{
    public static function getMimeType(): string
    {
        return "image/jpeg";
    }
}