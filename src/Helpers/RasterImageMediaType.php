<?php

namespace PixlMint\Media\Helpers;

use GdImage;
use PixlMint\Media\Contracts\MediaProcessor;
use PixlMint\Media\Contracts\ScalableMediaProcessor;
use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;
use PixlMint\Media\Models\Mime;
use PixlMint\Media\Models\ScaledMedia;

class RasterImageMediaType extends AbstractMediaTypeHelper implements MediaProcessor, ScalableMediaProcessor
{
    public static function getDefaultSizes(): array
    {
        return [100, 500, 1080];
    }

    public static function getName(): string
    {
        return 'Images';
    }

    public function getDefaultScaled(): string
    {
        return '1080';
    }

    public static function getScaledExtension(): string
    {
        return 'webp';
    }

    public static function getApplicableExtensions(): array
    {
        return ['jpg', 'jpeg', 'webp', 'png'];
    }

    public function deleteMedia(Media $media, bool $dryRun = false): bool|array
    {
        return parent::deleteMedia($media, $dryRun);
    }

    /**
     * @return array|Media[]
     */
    public function loadMedia(MediaGalleryDirectory $directory): array
    {
        return parent::loadMedia($directory);
    }

    public function storeMedia(array $file, MediaGalleryDirectory $directory): Media
    {
        $media = new Media(self::generateFileName($file), $directory);

        $this->outputFile($file['tmp_name'], $media);

        $media = $this->scale($media);

        return $media;
    }

    public function updateMedia(array $file, Media $media): void
    {
        $this->outputFile($file['tmp_name'], $media);
        $this->scale($media);
    }

    /**
     * Save the uploaded image as webp in the correct orientation to disk
     */
    protected function outputFile(string $mediaPath, Media $media)
    {
        // Rotate Image
        $image = $this->getImageFromPath($mediaPath);
        $exif = self::readExif($mediaPath);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
            }
        }

        // Save rotated image
        imagewebp($image, $media->getAbsolutePath());
    }

    private static function readExif(string $filePath): array
    {
        $errorReporting = error_reporting();
        error_reporting(E_ERROR | E_PARSE);
        $exif = exif_read_data($filePath);
        error_reporting($errorReporting);
        if (!$exif) {
            $exif = [
                'MimeType' => Mime::init(mime_content_type($filePath))->getType(),
            ];
        }

        return $exif;
    }

    private function getImageFromPath(string $mediaPath): bool|GdImage
    {
        $mime = Mime::init(mime_content_type($mediaPath));
        return match ($mime->getContainer()) {
            'jpeg', 'jpg' => imagecreatefromjpeg($mediaPath),
            'png' => imagecreatefrompng($mediaPath),
            default => false,
        };
    }

    protected function scale(Media $media): Media
    {
        foreach ($this::getDefaultSizes() as $size) {
            $media->addScaled(new ScaledMedia($size, 'webp'));
            $this->compressImage($media, $size);
        }

        return $media;
    }

    private function compressImage(Media $media, int $size): void
    {
        $targetDirectory = $media->getAbsoluteDirectory($size);

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        $targetPath = implode(DIRECTORY_SEPARATOR, [$targetDirectory, $media->getName() . '.' . $media->getScaled($size)->getFileExtension()]);

        // Scale down image
        $imgObject = imagecreatefromstring(file_get_contents($media->getAbsolutePath()));
        $scaled = imagescale($imgObject, $size);

        // Save scaled down version in new path
        imagewebp($scaled, $targetPath);
    }
}
