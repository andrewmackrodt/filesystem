<?php

declare(strict_types=1);

namespace Test\Unit;

use Denimsoft\File\Directory;
use Denimsoft\File\Filesystem;
use PHPUnit\Framework\TestCase;

class DirectoryTest extends TestCase
{
    public function testMagicPropertiesDoNotBlock()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->never())->method($this->anything());
        $file = new Directory(__DIR__, $filesystem);

        $this->assertSame('unit', $file->basename);
        $this->assertSame(null, $file->extension);
        $this->assertSame('unit', $file->filename);
        $this->assertSame(dirname(__DIR__), $file->path);
        $this->assertSame(dirname(dirname(__DIR__)), $file->parent->path);
        $this->assertSame(__DIR__, $file->pathname);
    }
}
