<?php

namespace PixlMint\Media\Helpers;

use PixlMint\CMS\Helpers\CMSConfiguration;
use PixlMint\Media\Contracts\MediaProcessor;
use PixlMint\Media\Contracts\ScalableMediaProcessor;
use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;
use PixlMint\Media\Models\MediaList;

class MediaHelper
{
    /** @var array|MediaProcessor[] $mediaHelpers */
    private array $mediaHelpers = [];

    private string $mediaDir;

    public function __construct(WebpMediaType $jpegMediaType, VideoMediaType $videoMediaType, SvgMediaType $svgMediaType, CMSConfiguration $cmsConfiguration)
    {
        $this->mediaHelpers['img'] = $jpegMediaType;
        $this->mediaHelpers['vid'] = $videoMediaType;
        $this->mediaHelpers['svg'] = $svgMediaType;
        $this->mediaDir = $cmsConfiguration->mediaDir();
    }

    public function store(MediaGalleryDirectory $directory, array $file): array
    {
        $targetDirectory = implode(DIRECTORY_SEPARATOR, [$this->mediaDir, $directory->getRelativePath()]);
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        $helper = $this->getMediaHelper($file['name']);
        $media = $helper->storeMedia($file, $directory);
        $tmpArr = $media->toFrontendArray();
        if ($helper instanceof ScalableMediaProcessor) {
            $tmpArr['scaled']['default'] = $media->getMediaPath($helper->getDefaultScaled());
        }

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

    public function updateMedia(Media $media, array $file): void
    {
        $helper = $this->getMediaHelper($file['name']);
        $helper->updateMedia($file, $media);
    }

    /**
     * @return array|MediaList[]
     */
    public function loadMedia(MediaGalleryDirectory $directory): array
    {
        $ret = [];
        foreach ($this->mediaHelpers as $slug => $helper) {
            $mediaArray = $helper->loadMedia($directory);
            $mediaList = new MediaList($helper::getName(), $slug, $helper instanceof ScalableMediaProcessor ? $helper->getDefaultScaled() : '');
            $mediaList->setMedias($mediaArray);
            $ret[] = $mediaList;
        }
        return $ret;
    }

    public function findMedia(string $path): ?Media
    {
        $pathinfo = pathinfo($path);
        $dirname = $pathinfo['dirname'];
        $extension = $pathinfo['extension'];
        $basename = $pathinfo['basename'];

        $mediaDirectory = MediaGalleryDirectory::fromPath($dirname);

        $extensionFound = false;
        foreach ($this->mediaHelpers as $helper) {
            /** @var MediaProcessor $helper */
            if (in_array($extension, $helper->getApplicableExtensions())) {
                $extensionFound = true;
                foreach ($helper->loadMedia($mediaDirectory) as $media) {
                    /** @var Media $media */
                    if ($media->getName() === $basename) {
                        return $media;
                    }
                }
            }
        }
        if (!$extensionFound) {
            throw new \Exception("Unable to match extesion $extension");
        }

        return null;
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

    public function getMediaHelper(string $fileName): MediaProcessor
    {
        foreach ($this->mediaHelpers as $processor) {
            foreach ($processor::getApplicableExtensions() as $ext) {
                if (FileNameHelper::extensionMatches($fileName, $processor::getApplicableExtensions())) {
                    return $processor;
                }
            }
        }

        throw new \Exception(sprintf('No applicable MediaProcessor found for file %s', $fileName));
    }
}
