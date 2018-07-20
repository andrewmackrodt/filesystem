<?php

declare(strict_types=1);

namespace Test\Unit;

use Denimsoft\File\File;
use Denimsoft\File\Filesystem;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testMagicPropertiesDoNotBlock()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->never())->method($this->anything());
        $file = new File(__FILE__, $filesystem);

        $this->assertSame('FileTest.php', $file->basename);
        $this->assertSame('php', $file->extension);
        $this->assertSame('FileTest.php', $file->filename);
        $this->assertSame(__DIR__, $file->path);
        $this->assertSame(dirname(__DIR__), $file->parent->path);
        $this->assertSame(__FILE__, $file->pathname);
    }
}
