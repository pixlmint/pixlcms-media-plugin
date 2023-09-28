<?php

namespace tests;

use App\Helpers\Media\ImageMediaType;
use App\Helpers\Media\MediaFactory;
use App\Helpers\Media\VideoMediaType;
use App\Models\Media;
use PHPUnit\Framework\TestCase;

class MediaIndexerTest extends TestCase
{
    public function setUp(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
    }

    public function testFindMedia(): void
    {
        $helpers = [new ImageMediaType(), new VideoMediaType()];
        $testMedia = MediaFactory::run('/var/www/html/media/December/2022-12-12/c763bbaff22ea0a058a5c9d5b7adb610a5832f96DSCF2161.jpg', $helpers);

        $this->assertInstanceOf(Media::class, $testMedia);
        $this->assertEquals('/media/December/2022-12-12/c763bbaff22ea0a058a5c9d5b7adb610a5832f96DSCF2161.jpg', $testMedia->getMediaPath());
    }
}
