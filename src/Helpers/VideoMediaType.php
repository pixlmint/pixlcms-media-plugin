<?php

namespace PixlMint\Media\Helpers;

use Mhor\MediaInfo\Container\MediaInfoContainer;
use Mhor\MediaInfo\MediaInfo;
use Mhor\MediaInfo\Type\Video;
use PixlMint\CMS\Helpers\CMSConfiguration;
use PixlMint\Media\Contracts\MediaProcessor;
use PixlMint\Media\Models\EncodingJob;
use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;
use PixlMint\Media\Models\MediaSize;
use PixlMint\Media\Models\ScaledMedia;
use PixlMint\Media\Repository\EncodingJobRepository;

class VideoMediaType extends AbstractMediaTypeHelper implements MediaProcessor
{
    const DEFAULT_HEIGHT = 720;
    const DEFAULT_FPS = 30;
    const ENCODED_DIR = 'encode';
    private EncodingJobRepository $encodingJobRepository;

    public function __construct(CMSConfiguration $cmsConfiguration, EncodingJobRepository $encodingJobRepository)
    {
        parent::__construct($cmsConfiguration);
        $this->encodingJobRepository = $encodingJobRepository;
    }

    public static function getDefaultSizes(): array
    {
        return [self::ENCODED_DIR];
    }

    public static function getScaledExtension(): string
    {
        return 'webm';
    }

    public static function getMimeType(): string
    {
        return 'video/*';
    }

    public static function getName(): string
    {
        return 'Videos';
    }

    public static function getApplicableExtensions(): array
    {
        return ['mov', 'webm', 'mp4', 'mkv'];
    }

    public function getDefaultScaled(): string
    {
        return self::ENCODED_DIR;
    }

    public function loadMedia(MediaGalleryDirectory $directory): array
    {
        return parent::loadMedia($directory);
    }

    public function storeMedia(array $file, MediaGalleryDirectory $directory): Media
    {
        $media = new Media(self::generateFileName($file), $directory);
        $media->addScaled(new ScaledMedia(self::ENCODED_DIR, 'webm'));

        move_uploaded_file($file['tmp_name'], $media->getAbsolutePath());

        $this->scale($media);

        return $media;
    }

    protected function scale(Media $media): array
    {
        $encode = $this->getEncoderSettings($media);

        $this->encodingJobRepository->set($encode);

        return [self::ENCODED_DIR => $media->getMediaPath(self::ENCODED_DIR)];
    }

    private function getEncoderSettings(Media $media): EncodingJob
    {
        $mediainfo = new MediaInfo();
        $mic = $mediainfo->getInfo($media->getAbsolutePath());

        $encodingJob = new EncodingJob(-1, $media->getAbsolutePath(), $media->getAbsolutePath(self::ENCODED_DIR));
        $video = $this->getVideo($mic);

        $size = new MediaSize($video->get('height')->getAbsoluteValue(), $video->get('width')->getAbsoluteValue());
        $aspectRatio = $video->get('display_aspect_ratio')->getAbsoluteValue();
        $fps = $video->get('frame_rate')->getAbsoluteValue();

        if ($fps > self::DEFAULT_FPS) {
            $fps = self::DEFAULT_FPS;
        }
        if ($size->getHeight() > self::DEFAULT_HEIGHT) {
            $size->setHeight(self::DEFAULT_HEIGHT);
            $size->setWidth(round($size->getHeight() * $aspectRatio));
        }

        $encodingJob->setFramerate($fps);
        $encodingJob->setHeight($size->getHeight());
        $encodingJob->setWidth($size->getWidth());

        return $encodingJob;
    }

    private function getVideo(MediaInfoContainer $mic): ?Video
    {
        foreach ($mic->getVideos() as $video) {
            return $video;
        }

        return null;
    }

    public function deleteMedia(Media $media, bool $dryRun = false): bool|array
    {
        return parent::deleteMedia($media, $dryRun);
    }
}
