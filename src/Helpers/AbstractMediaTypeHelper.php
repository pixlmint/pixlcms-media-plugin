<?php

namespace PixlMint\Media\Helpers;

use Exception;
use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;
use PixlMint\Media\Models\Mime;
use PixlMint\CMS\Helpers\CMSConfiguration;

abstract class AbstractMediaTypeHelper
{
    public function deleteMedia(Media $media, bool $dryRun = false): bool|array
    {
        $files = $this->getFilesToDelete($media);

        if ($dryRun) {
            return $files;
        }

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    private function getFilesToDelete(Media $media): array
    {
        $ret = [];
        if (!is_file($media->getAbsolutePath())) {
            return $ret;
        } else {
            $ret[] = $media->getAbsolutePath();
        }

        foreach ($media->getAllScaled() as $scaled) {
            if (is_file($media->getAbsolutePath($scaled->getScaleName()))) {
                $ret[] = $media->getAbsolutePath($scaled->getScaleName());
            }
        }

        return $ret;
    }

    public function loadMedia(MediaGalleryDirectory $directory): array
    {
        $mediaDir = CMSConfiguration::mediaDir();
        $media = [];
        $dir = $directory->getRelativePath();
        if (!is_dir("${mediaDir}/${dir}")) {
            return $media;
        }
        foreach (scandir("${mediaDir}/${dir}") as $file) {
            if ($file === '.' || $file === '..' || is_dir("${mediaDir}/${dir}/${file}")) {
                continue;
            }
            if ($this->isApplicableMediaMime("${mediaDir}/${dir}/${file}")) {
                $media[] = MediaFactory::run("${mediaDir}/${dir}/${file}", [$this]);
            }
        }

        return $media;
    }

    public static function generateFileName(array $file)
    {
        return sha1_file($file['tmp_name']) . $file['name'];
    }

    public static function getMimeType(): string
    {
        throw new Exception('Mime Type not defined');
    }

    protected function isApplicableMediaMime(string $file): bool
    {
        $fileMime = Mime::init(mime_content_type($file));
        $testMime = Mime::init(static::getMimeType());

        return MimeHelper::compareMimeTypes($testMime, $fileMime);
    }
}