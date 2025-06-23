<?php

namespace PixlMint\Media\Helpers;

use Nacho\Contracts\PageManagerInterface;
use Nacho\Helpers\PageManager;
use Nacho\Models\PicoPage;
use PixlMint\CMS\Helpers\CMSConfiguration;
use PixlMint\Media\Contracts\MediaProcessor;
use PixlMint\Media\Contracts\ScalableMediaProcessor;
use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;
use PixlMint\Media\Models\MediaList;
use PixlMint\Media\Models\ScaledMedia;

class MediaHelper
{
    /** @var array|MediaProcessor[] $mediaHelpers */
    private array $mediaHelpers = [];

    private string $mediaDir;
    private PageManagerInterface $pageManager;

    public function __construct(WebpMediaType $jpegMediaType, VideoMediaType $videoMediaType, SvgMediaType $svgMediaType, CMSConfiguration $cmsConfiguration, PageManagerInterface $pageManager)
    {
        $this->mediaHelpers['img'] = $jpegMediaType;
        $this->mediaHelpers['vid'] = $videoMediaType;
        $this->mediaHelpers['svg'] = $svgMediaType;
        $this->mediaDir = $cmsConfiguration->mediaDir();
        $this->pageManager = $pageManager;
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
        /* print("dirname: $dirname, extension: $extension, basename: $basename\n"); */
        /* print_r($pathinfo); */

        $extensionFound = false;
        foreach ($this->mediaHelpers as $helper) {
            /** @var MediaProcessor $helper */
            if (in_array($extension, $helper->getApplicableExtensions())) {
                $extensionFound = true;
                foreach ($helper->loadMedia($mediaDirectory) as $media) {
                    /** @var Media $media */
                    /* print("{$media->getName()}\n"); */
                    if ($media->getName() === $basename || str_starts_with($basename, $media->getName())) {
                        return $media;
                    }
                }
            }
        }
        if (!$extensionFound) {
            throw new \Exception("Unable to match extension $extension");
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
        return $this->mediaHelpers[$this->getMediaType($fileName)];
    }

    public function getMediaType(string $fileName): string
    {
        foreach ($this->mediaHelpers as $mediaType => $processor) {
            foreach ($processor::getApplicableExtensions() as $ext) {
                if (FileNameHelper::extensionMatches($fileName, $processor::getApplicableExtensions())) {
                    return $mediaType;
                }
            }
        }

        throw new \Exception(sprintf('No applicable media type found for file %s', $fileName));
    }

    public function getMediaPage(Media $media): ?PicoPage
    {
        $pageDir = $media->getPageBase();
        return $this->pageManager->getPage('/' . $pageDir);
    }
}
