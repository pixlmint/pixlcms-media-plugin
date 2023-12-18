<?php

namespace PixlMint\Media\Contracts;

use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;

interface ScalableMediaProcessor
{
    public static function getDefaultSizes(): array;

    public static function getScaledExtension(): string;

    public function getDefaultScaled(): string;
}