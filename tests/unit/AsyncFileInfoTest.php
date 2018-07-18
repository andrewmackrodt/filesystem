<?php

declare(strict_types=1);

namespace Test\Unit;

use Denimsoft\File\AsyncFileInfo;
use Denimsoft\File\Filesystem;
use PHPUnit\Framework\TestCase;

class AsyncFileInfoTest extends TestCase
{
    public function testMagicPropertiesDoNotBlock()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->never())->method($this->anything());
        $file = new AsyncFileInfo(__FILE__, $filesystem);

        $this->assertSame('AsyncFileInfoTest.php', $file->basename);
        $this->assertSame('php', $file->extension);
        $this->assertSame('AsyncFileInfoTest.php', $file->filename);
        $this->assertSame(__DIR__, $file->path);
        $this->assertSame(dirname(__DIR__), $file->pathinfo->path);
        $this->assertSame(__FILE__, $file->pathname);
    }
}
