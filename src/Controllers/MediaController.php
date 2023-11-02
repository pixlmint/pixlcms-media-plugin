<?php

namespace PixlMint\Media\Controllers;

use Nacho\Models\HttpResponse;
use Nacho\Nacho;
use PixlMint\Media\Helpers\MediaHelper;
use PixlMint\Media\Models\MediaGalleryDirectory;
use Nacho\Controllers\AbstractController;
use Nacho\Models\Request;
use PixlMint\CMS\Helpers\CustomUserHelper;

class MediaController extends AbstractController
{
    private MediaHelper $mediaHelper;

    public function __construct(Nacho $nacho)
    {
        $this->mediaHelper = new MediaHelper();
        parent::__construct($nacho);
    }

    /**
     * GET: /api/admin/gallery/upload
     */
    public function uploadMedia(): HttpResponse
    {
        if (!$this->isGranted(CustomUserHelper::ROLE_EDITOR)) {
            return $this->json(['message' => 'You are not authenticated'], 401);
        }
        if (!key_exists('gallery', $_REQUEST)) {
            return $this->json(['message' => 'Please define the target Gallery'], 400);
        }
        $gallery = $_REQUEST['gallery'];
        $mediaDirectory = MediaGalleryDirectory::fromPath($gallery);
        $files = $_FILES;

        $uploadedFiles = $this->mediaHelper->storeAll($mediaDirectory, $files);

        return $this->json(['message' => 'uploaded files', 'files' => $uploadedFiles]);
    }

    // /api/admin/gallery/load
    public function loadMediaForEntry(): HttpResponse
    {
        if (!$this->isGranted(CustomUserHelper::ROLE_EDITOR)) {
            return $this->json(['message' => 'You are not authenticated'], 401);
        }
        if (!key_exists('gallery', $_REQUEST)) {
            return $this->json(['message' => 'Please define the target Gallery'], 400);
        }

        $gallery = $_REQUEST['gallery'];
        $mediaDirectory = MediaGalleryDirectory::fromPath($gallery);

        $media = array_map(function ($m) {
            return $m->toArray();
        }, $this->mediaHelper->loadMedia($mediaDirectory));

        return $this->json(["media" => $media]);
    }

    // /api/admin/media/delete
    public function deleteMedia(Request $request): HttpResponse
    {
        if (!$this->isGranted(CustomUserHelper::ROLE_EDITOR)) {
            return $this->json(['message' => 'You are not authenticated'], 401);
        }
        if (!key_exists('media', $_REQUEST)) {
            return $this->json(['message' => 'Please define the media to delete'], 400);
        }

        $media = $request->getBody()['media'];

        $delete = $this->mediaHelper->delete($media);

        return $this->json($delete);
    }
}
