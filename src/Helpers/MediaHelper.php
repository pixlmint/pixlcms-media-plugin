<?php

namespace PixlMint\Media\Helpers;

use Nacho\Nacho;
use PixlMint\CMS\Helpers\CMSConfiguration;
use PixlMint\Media\Contracts\MediaProcessor;
use PixlMint\Media\Models\MediaGalleryDirectory;
use PixlMint\Media\Models\MediaList;
use PixlMint\Media\Models\Mime;

class MediaHelper
{
    /** @var array|MediaProcessor[] $mediaHelpers */
    private array $mediaHelpers = [];

    private string $mediaDir;

    public function __construct(ImageMediaType $imageMediaType, VideoMediaType $videoMediaType, CMSConfiguration $cmsConfiguration)
    {
        $this->mediaHelpers['img'] = $imageMediaType;
        $this->mediaHelpers['vid'] = $videoMediaType;
        $this->mediaDir = $cmsConfiguration->mediaDir();
    }

    public function store(MediaGalleryDirectory $directory, array $file): array
    {
        $targetDirectory = implode(DIRECTORY_SEPARATOR, [$this->mediaDir, $directory->getRelativePath()]);
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        $helper = $this->getMediaHelper(Mime::init($file['type']));
        $media = $helper->storeMedia($file, $directory);
        $tmpArr = $media->toFrontendArray();
        $tmpArr['scaled']['default'] = $media->getMediaPath($helper->getDefaultScaled());

        return $tmpArr;
    }

    public function storeAll(MediaGalleryDirectory $directory, array $files): array
    {
        $uploadedFiles = [];
        foreach ($files as $file) {
            $uploadedFiles[] = $this->store($directory, $file);
        }

        return $uploadedFiles;
    }

    /**
     * @return array|MediaList[]
     */
    public function loadMedia(MediaGalleryDirectory $directory): array
    {
        $ret = [];
        foreach ($this->mediaHelpers as $slug => $helper) {
            $mediaArray = $helper->loadMedia($directory);
            $mediaList = new MediaList($helper::getName(), $slug, $helper->getDefaultScaled());
            $mediaList->setMedias($mediaArray);
            $ret[] = $mediaList;
        }
        return $ret;
    }

    public function delete(string $media): array
    {
        $splPath = explode('/', $media);
        $filename = array_pop($splPath);
        $directory = MediaGalleryDirectory::fromPath(implode('/', $splPath));
        $mediaToDelete = MediaFactory::run($directory, $filename, $this->mediaHelpers);
        $delete = [];
        foreach ($this->mediaHelpers as $helper) {
            $delete[] = $helper->deleteMedia($mediaToDelete);
        }
        return $delete;
    }

    public function getMediaHelper(Mime $mime): MediaProcessor
    {
        foreach ($this->mediaHelpers as $mediaHelper) {
            $testMime = Mime::init($mediaHelper::getMimeType());
            if (MimeHelper::compareMimeTypes($testMime, $mime)) {
                return $mediaHelper;
            }
        }

        throw new \Exception(sprintf('The Mime Type %s is not supported', $mime->printMime()));
    }
}