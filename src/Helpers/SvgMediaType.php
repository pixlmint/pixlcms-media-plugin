<?php

namespace PixlMint\Media\Helpers;

use PixlMint\Media\Contracts\MediaProcessor;
use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;

class SvgMediaType extends AbstractMediaTypeHelper implements MediaProcessor
{
    public static function getName(): string
    {
        return "SVG";
    }

    public static function getMimeType(): string
    {
        return "image/svg+xml";
    }

    public static function getApplicableExtensions(): array
    {
        return ["svg"];
    }

    public function storeMedia(array $file, MediaGalleryDirectory $directory): Media
    {
        $media = new Media(self::generateFileName($file), $directory);

        file_put_contents($media->getAbsolutePath(), file_get_contents($file['tmp_name']));

        return $media;
    }
}