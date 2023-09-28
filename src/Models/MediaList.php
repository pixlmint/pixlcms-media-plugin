<?php

namespace PixlMint\Media\Models;

use ArrayIterator;
use Iterator;

class MediaList extends ArrayIterator implements Iterator
{
    private array $medias = [];
    private string $name;
    private string $slug;
    private string $defaultScaled;

    public function __construct(string $name, string $slug, string $defaultScaled)
    {
        parent::__construct();
        $this->name = $name;
        $this->slug = $slug;
        $this->defaultScaled = $defaultScaled;
    }

    public function setMedias(array $medias): void
    {
        $this->medias = $medias;
        $this->rewind();
    }

    public function addMedia(Media $media): void
    {
        $this->medias[] = $media;
        $this->rewind();
    }

    /**
     * @return array|Media[]
     */
    public function getMedias(): array
    {
        return $this->medias;
    }

    public function getEssentialMediaPaths(): array
    {
        return array_map(function (Media $media) {
            return [
                'source' => $media->getMediaPath(),
                'default' => $media->getMediaPath($this->defaultScaled),
            ];
        }, $this->medias);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getDefaultScaled(): string
    {
        return $this->defaultScaled;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'media' => $this->getEssentialMediaPaths(),
        ];
    }
}