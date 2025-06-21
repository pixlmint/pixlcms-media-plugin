<?php

namespace PixlMint\Media\Models;

use Nacho\Contracts\ArrayableInterface;
use Nacho\Nacho;
use Nacho\ORM\AbstractModel;
use Nacho\ORM\ModelInterface;
use Nacho\ORM\TemporaryModel;
use PixlMint\CMS\Helpers\CMSConfiguration;

class Media extends AbstractModel implements ArrayableInterface, ModelInterface
{
    private string $name;
    private MediaGalleryDirectory $directory;
    /** @var array|ScaledMedia[] */
    private array $scaled;

    public function __construct(string $name, MediaGalleryDirectory $directory, array $scaled = [])
    {
        $this->id = 0;
        $this->name = $name;
        $this->directory = $directory;
        $this->scaled = $scaled;
    }

    public static function init(TemporaryModel $data, int $id): ModelInterface
    {
        $scaled = array_map(function (mixed $scale) {
            return new ScaledMedia($scale->get('scaleName'), $scale->get('fileExtension'));
        }, $data->get('scaled'));

        $mediaDirectory = new MediaGalleryDirectory($data->get('month'), $data->get('day'));

        return new Media($data->get('name'), $mediaDirectory, $scaled);
    }

    public function getMime(): ?Mime
    {
        $mime = mime_content_type($this->getAbsolutePath());
        if ($mime) {
            return Mime::init($mime);
        }
        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addScaled(ScaledMedia $scaled): void
    {
        $this->scaled[] = $scaled;
    }

    public function getScaled(string $size): ?ScaledMedia
    {
        foreach ($this->scaled as $scaled) {
            if ($scaled->getScaleName() === $size) {
                return $scaled;
            }
        }

        return null;
    }

    public function getAllScaled(): array
    {
        return $this->scaled;
    }

    /**
     * Relative Path for use inside the browser
     */
    public function getMediaPath(?string $size = null): string
    {
        $baseDir = [$this->getMediaBaseUrl(), $this->directory->getRelativePath()];
        if ($size) {
            $baseDir[] = $size;
            $baseDir[] = $this->name . '.' . $this->getScaled($size)->getFileExtension();
        } else {
            $baseDir[] = $this->name;
        }
        return implode(DIRECTORY_SEPARATOR, $baseDir);
    }

    /**
     * Absolute File Path
     */
    public function getAbsolutePath(?string $size = null): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . $this->getMediaPath($size);
    }

    public function getPageBase(): string
    {
        return $this->directory->getRelativePath();
    }

    /**
     * Relative Path of the directory the media is in
     */
    public function getDirectory(?string $size = null): string
    {
        $baseDir = [$this->getMediaBaseUrl(), $this->directory->getRelativePath()];
        if ($size) {
            $baseDir[] = $size;
        }
        return implode(DIRECTORY_SEPARATOR, $baseDir);
    }

    /**
     * Absolute Path of the directory the media is in
     */
    public function getAbsoluteDirectory(?string $size = null): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . $this->getDirectory($size);
    }

    public function toArray(): array
    {
        return [
            'directory' => $this->directory->getRelativePath(),
            'name' => $this->name,
            'scaled' => array_map(function (ScaledMedia $s) {
                return $s->toArray();
            }, $this->scaled),
        ];
    }

    public function toFrontendArray(): array
    {
        $scaledArray = [];
        foreach ($this->scaled as $s) {
            $scaledArray[$s->getScaleName()] = $this->getMediaPath($s->getScaleName());
        }
        return [
            'path' => $this->getMediaPath(),
            'scaled' => $scaledArray,
        ];
    }

    private function getMediaBaseUrl(): string
    {
        return Nacho::$container->get(CMSConfiguration::class)->mediaBaseUrl();
    }
}
