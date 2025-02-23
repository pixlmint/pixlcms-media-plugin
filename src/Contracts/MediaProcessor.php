<?php

namespace PixlMint\Media\Contracts;

use PixlMint\Media\Models\Media;
use PixlMint\Media\Models\MediaGalleryDirectory;

interface MediaProcessor
{
    public static function getMimeType(): string;

    public static function getName(): string;

    public static function getApplicableExtensions(): array;

    public function deleteMedia(Media $media, bool $dryRun): bool|array;

    public function storeMedia(array $file, MediaGalleryDirectory $directory): Media;

    public function updateMedia(array $file, Media $media): void;

    public function loadMedia(MediaGalleryDirectory $directory): array;
}
