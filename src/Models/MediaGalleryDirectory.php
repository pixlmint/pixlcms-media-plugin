<?php

namespace PixlMint\Media\Models;

class MediaGalleryDirectory
{
    private array $galleryParts;

    public function __construct(array $gallery)
    {
        $this->galleryParts = $gallery;
    }

    public static function fromPath(string $galleryPath)
    {
        $galleryPath = ltrim($galleryPath, '/');
        $galleryPath = ltrim($galleryPath, "media/");
        $splPath = explode('/', $galleryPath);

        if (count($splPath) > 0 && is_numeric($splPath[count($splPath) - 1])) {
            // it's probably the size suffix, remove that
            array_pop($splPath);
        }

        return new self($splPath);
    }

    /**
     * Returns the relative Path of the media directory WITHOUT leading slash
     */
    public function getRelativePath(): string
    {
        return implode('/', $this->galleryParts);
    }
}
