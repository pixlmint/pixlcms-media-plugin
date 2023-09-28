<?php

namespace Tests\Helpers;

use App\Models\Mime;
use PHPUnit\Framework\TestCase;

class MimeTest extends TestCase
{
    public function testInit()
    {
        $mime = Mime::init('text/plain');
        $this->assertInstanceOf(Mime::class, $mime);
    }

    public function testGetType()
    {
        $mime = new Mime('text', 'plain');
        $this->assertEquals('text', $mime->getType());
    }

    public function testGetContainer()
    {
        $mime = new Mime('text', 'plain');
        $this->assertEquals('plain', $mime->getContainer());
    }

    public function testIsAnyType()
    {
        $mime = new Mime('*', 'plain');
        $this->assertTrue($mime->isAnyType());
    }

    public function testIsNotAnyType()
    {
        $mime = new Mime('text', 'plain');
        $this->assertFalse($mime->isAnyType());
    }

    public function testIsAnyContainer()
    {
        $mime = new Mime('text', '*');
        $this->assertTrue($mime->isAnyContainer());
    }

    public function testIsNotAnyContainer()
    {
        $mime = new Mime('text', 'plain');
        $this->assertFalse($mime->isAnyContainer());
    }

    public function testPrintMime()
    {
        $mime = new Mime('text', 'plain');
        $this->assertEquals('text/plain', $mime->printMime());
    }

    public function testInitInvalidMime()
    {
        $this->expectException(\Exception::class);
        Mime::init('invalid-mime-type');
    }
}
