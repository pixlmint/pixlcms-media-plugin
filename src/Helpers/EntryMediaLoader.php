<?php

namespace PixlMint\Media\Helpers;

use PixlMint\Media\Contracts\MediaProcessor;
use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;

/**
 * A Class for loading media that belongs to a specific entry
 */
class EntryMediaLoader
{
    private MediaGalleryDirectory $directory;
    private MediaProcessor $processor;

    public function __construct(MediaGalleryDirectory $directory, MediaProcessor $processor)
    {
        $this->directory = $directory;
        $this->processor = $processor;
    }

    /**
     * @return array|Media[]
     */
    public static function run(MediaGalleryDirectory $directory, MediaProcessor $processor): array
    {
        $loader = new EntryMediaLoader($directory, $processor);
        return $loader->loadMedia();
    }

    /**
     * @return array|Media[]
     */
    public function loadMedia(): array
    {
        $processorMedia = $this->processor->loadMedia($this->directory);
        return array_map(function (Media $media) {
            return $this->mediaToArray($media);
        }, $processorMedia);
    }

    private function mediaToArray(Media $media): array
    {
        return [
            'source' => $media->getMediaPath(),
            'default' => $media->getMediaPath($this->processor->getDefaultScaled()),
        ];
    }
}