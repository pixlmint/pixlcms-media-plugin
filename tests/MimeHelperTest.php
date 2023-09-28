<?php

namespace Tests\Helpers;

use App\Helpers\Media\MimeHelper;
use App\Models\Mime;
use PHPUnit\Framework\TestCase;

class MimeHelperTest extends TestCase
{
    public function testSameMimeReturnsTrue()
    {
        $mime1 = Mime::init('image/png');
        $mime2 = Mime::init('image/png');

        $result = MimeHelper::compareMimeTypes($mime1, $mime2);

        $this->assertTrue($result);
    }

    public function testDifferentMimeReturnsFalse()
    {
        $mime1 = Mime::init('image/png');
        $mime2 = Mime::init('image/jpeg');

        $result = MimeHelper::compareMimeTypes($mime1, $mime2);

        $this->assertFalse($result);
    }

    public function testSameTypeReturnsTrue()
    {
        $mime1 = Mime::init('image/*');
        $mime2 = Mime::init('image/png');

        $result = MimeHelper::compareMimeTypes($mime1, $mime2);

        $this->assertTrue($result);
    }

    public function testDifferentTypeReturnsFalse()
    {
        $mime1 = Mime::init('image/*');
        $mime2 = Mime::init('audio/mpeg');

        $result = MimeHelper::compareMimeTypes($mime1, $mime2);

        $this->assertFalse($result);
    }

    public function testControlMimeIsAnyTypeReturnsTrue()
    {
        $mime1 = Mime::init('*/*');
        $mime2 = Mime::init('audio/mpeg');

        $result = MimeHelper::compareMimeTypes($mime1, $mime2);

        $this->assertTrue($result);
    }

    public function testControlMimeIsAnyContainerAndDifferentTypeReturnsFalse()
    {
        $mime1 = Mime::init('image/*');
        $mime2 = Mime::init('audio/mpeg');

        $result = MimeHelper::compareMimeTypes($mime1, $mime2);

        $this->assertFalse($result);
    }

    public function testControlMimeIsSameTypeAndAnyContainerReturnsTrue()
    {
        $mime1 = Mime::init('image/*');
        $mime2 = Mime::init('image/png');

        $result = MimeHelper::compareMimeTypes($mime1, $mime2);

        $this->assertTrue($result);
    }

    public function testControlMimeIsDifferentTypeAndSameContainerReturnsFalse()
    {
        $mime1 = Mime::init('image/*');
        $mime2 = Mime::init('video/mp4');

        $result = MimeHelper::compareMimeTypes($mime1, $mime2);

        $this->assertFalse($result);
    }

    public function testImageMimeBug()
    {
        $mime1 = Mime::init('image/*');
        $mime2 = Mime::init('image/webp');

        $result = MimeHelper::compareMimeTypes($mime1, $mime2);

        $this->assertTrue($result);
    }
}