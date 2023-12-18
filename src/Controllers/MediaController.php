<?php

namespace PixlMint\Media\Controllers;

use Nacho\Contracts\RequestInterface;
use Nacho\Models\HttpResponse;
use PixlMint\Media\Helpers\MediaHelper;
use PixlMint\Media\Models\MediaGalleryDirectory;
use Nacho\Controllers\AbstractController;
use PixlMint\CMS\Helpers\CustomUserHelper;

class MediaController extends AbstractController
{
    private MediaHelper $mediaHelper;

    public function __construct(MediaHelper $mediaHelper)
    {
        parent::__construct();
        $this->mediaHelper = $mediaHelper;
    }

    /**
     * GET: /api/admin/gallery/upload
     */
    public function uploadMedia(RequestInterface $request): HttpResponse
    {
        if (!$this->isGranted(CustomUserHelper::ROLE_EDITOR)) {
            return $this->json(['message' => 'You are not authenticated'], 401);
        }
        if (!key_exists('gallery', $_REQUEST)) {
            return $this->json(['message' => 'Please define the target Gallery'], 400);
        }
        $gallery = $_REQUEST['gallery'];
        $mediaDirectory = MediaGalleryDirectory::fromPath($gallery);
        if ($_FILES) {
            $files = $_FILES;
        } elseif (key_exists('files', $request->getBody())) {
            $files = array_map(function ($f) {
                $tmp_path = '/tmp/' . md5($f['data']);
                file_put_contents($tmp_path, $f['data']);
                return [
                    'tmp_name' => $tmp_path,
                    'name' => $f['name'],
                    'type' => $f['type'],
                ];
            }, $request->getBody()['files']);
        } else {
            return $this->json(['message' => 'No files posted'], 400);
        }

        $uploadedFiles = $this->mediaHelper->storeAll($mediaDirectory, $files);

        return $this->json(['message' => 'uploaded files', 'files' => $uploadedFiles]);
    }

    /**
     * POST: /api/admin/gallery/upload-b64
     */
    public function uploadBase64(RequestInterface $request): HttpResponse
    {
        if (!$this->isGranted(CustomUserHelper::ROLE_EDITOR)) {
            return $this->json(['message' => 'You are not authenticated'], 401);
        }
        if (!key_exists('gallery', $request->getBody()) || !key_exists('data', $request->getBody())) {
            return $this->json(['message' => 'Please define the target Gallery and the data'], 400);
        }

        $gallery = $request->getBody()['gallery'];
        $imageB64 = $request->getBody()['data'];
        $re = '/^data:(?<mime>[a-z]+\/[a-z]+);base64,(?<data>.*)/m';
        preg_match($re, $imageB64, $matches);
        $mime = $matches['mime'];
        $imageData = base64_decode($matches['data']);
        $mediaDirectory = MediaGalleryDirectory::fromPath($gallery);

        $filename = time() . '.jpg';

        $img = imagecreatefromstring($imageData);
        imagejpeg($img, '/tmp/' . $filename, 0);

        $storedImage = $this->mediaHelper->store($mediaDirectory, [
            'tmp_name' => '/tmp/' . $filename,
            'name' => $filename,
            'type' => $mime,
        ]);

        return $this->json($storedImage);
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
    public function deleteMedia(RequestInterface $request): HttpResponse
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
