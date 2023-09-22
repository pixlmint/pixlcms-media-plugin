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
        $splPath = explode('/', $galleryPath);

        return new self($splPath);
    }

    public function getMonth(): string
    {
        return $this->month;
    }

    public function getDay(): string
    {
        return $this->day;
    }

    /**
     * Returns the relative Path of the media directory WITHOUT leading slash
     */
    public function getRelativePath(): string
    {
        return implode('/', $this->galleryParts);
    }
}