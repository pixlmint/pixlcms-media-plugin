<?php

namespace PixlMint\Media\Helpers;

use PixlMint\Media\Contracts\MediaProcessor;
use PixlMint\Media\Contracts\ScalableMediaProcessor;
use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;
use PixlMint\Media\Models\ScaledMedia;

class MediaFactory
{
    private MediaGalleryDirectory $directory;
    private string $name;
    /** @var array|MediaProcessor[]  */
    private array $mediaHelpers;

    public function __construct(MediaGalleryDirectory $directory, string $name, array $mediaHelpers = [])
    {
        $this->directory = $directory;
        $this->name = $name;
        $this->mediaHelpers = $mediaHelpers;
    }

    public static function run(MediaGalleryDirectory $directory, string $name, array $mediaHelpers = []): Media
    {
        $indexer = new MediaFactory($directory, $name, $mediaHelpers);
        return $indexer->findMedia();
    }

    public function findMedia(): Media
    {
        $processor = $this->getMediaHelper();

        if ($processor instanceof ScalableMediaProcessor) {
            $scaled = $this->getScaled($processor);
        } else {
            $scaled = [];
        }

        return new Media($this->name, $this->directory, $scaled);
    }

    private function getScaled(MediaProcessor $processor): array
    {
        $scaled = [];
        foreach($processor::getDefaultSizes() as $size) {
            $scaled[] = new ScaledMedia($size, $processor::getScaledExtension());
        }

        return $scaled;
    }

    private function getMediaHelper(): MediaProcessor
    {
        foreach ($this->mediaHelpers as $processor) {
            if (FileNameHelper::extensionMatches($this->name, $processor::getApplicableExtensions())) {
                return $processor;
            }
        }

        throw new \Exception('Unable to find an applicable Media Processor for ' . $this->name);
    }
}
