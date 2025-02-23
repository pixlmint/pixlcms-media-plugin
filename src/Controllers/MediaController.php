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
     * POST: /api/admin/gallery/upload
     */
    public function uploadMedia(RequestInterface $request): HttpResponse
    {
        if (!$this->isGranted(CustomUserHelper::ROLE_EDITOR)) {
            return $this->json(['message' => 'You are not authenticated'], 401);
        }
        if (!$request->getBody()->has('gallery')) {
            return $this->json(['message' => 'Please define the target Gallery'], 400);
        }
        $gallery = $request->getBody()->get('gallery');
        $mediaDirectory = MediaGalleryDirectory::fromPath($gallery);
        if ($_FILES) {
            $files = $_FILES;
        } elseif ($request->getBody()->has('files')) {
            $files = array_map(function ($f) {
                $tmp_path = '/tmp/' . md5($f['data']);
                file_put_contents($tmp_path, $f['data']);
                return [
                    'tmp_name' => $tmp_path,
                    'name' => $f['name'],
                    'type' => $f['type'],
                ];
            }, $request->getBody()->get('files'));
        } else {
            return $this->json(['message' => 'No files posted'], 400);
        }

        $uploadedFiles = $this->mediaHelper->storeAll($mediaDirectory, $files);

        return $this->json(['message' => 'uploaded files', 'files' => $uploadedFiles]);
    }

    /**
     * PUT:  /api/admin/gallery/media/replace
     */
    public function replaceMedia(RequestInterface $request): HttpResponse
    {
        if (!$this->isGranted(CustomUserHelper::ROLE_EDITOR)) {
            return $this->json(['message' => 'You are not authenticated'], 401);
        }
        if (!$request->getBody()->has('media')) {
            return $this->json(['message' => 'Media to replace needs to be defined'], 400);
        }
        $existingMedia = $request->getBody()->get('media');
        $media = $this->mediaHelper->findMedia($existingMedia);
        if (is_null($media)) {
            return $this->json(["message" => "Unable to find media $existingMedia"], 404);
        }
        if ($_FILES) {
            $files = $_FILES;
        } elseif ($request->getBody()->has('files')) {
            $files = array_map(function ($f) {
                $tmp_path = '/tmp/' . md5($f['data']);
                file_put_contents($tmp_path, $f['data']);
                return [
                    'tmp_name' => $tmp_path,
                    'name' => $f['name'],
                    'type' => $f['type'],
                ];
            }, $request->getBody()->get('files'));
        } else {
            return $this->json(['message' => 'No files posted'], 400);
        }

        $this->mediaHelper->updateMedia($media, array_pop($files));

        return $this->json(['message' => 'updated file']);
    }

    /**
     * POST: /api/admin/gallery/upload-b64
     */
    public function uploadBase64(RequestInterface $request): HttpResponse
    {
        if (!$this->isGranted(CustomUserHelper::ROLE_EDITOR)) {
            return $this->json(['message' => 'You are not authenticated'], 401);
        }
        if (!$request->getBody()->has('gallery') || !$request->getBody()->has('data')) {
            return $this->json(['message' => 'Please define the target Gallery and the data'], 400);
        }

        $gallery = $request->getBody()->get('gallery');
        $imageB64 = $request->getBody()->get('data');
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
    public function loadMediaForEntry(RequestInterface $request): HttpResponse
    {
        if (!$this->isGranted(CustomUserHelper::ROLE_EDITOR)) {
            return $this->json(['message' => 'You are not authenticated'], 401);
        }
        if (!$request->getBody()->has('gallery')) {
            return $this->json(['message' => 'Please define the target Gallery'], 400);
        }

        $gallery = $request->getBody()->get('gallery');
        if (!str_starts_with($gallery, '/')) {
            $gallery = '/' . $gallery;
        }
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
