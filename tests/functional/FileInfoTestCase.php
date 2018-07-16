<?php

declare(strict_types=1);

namespace Test\Functional;

use Amp\File\EioDriver;
use Amp\File\StatCache;
use Amp\Loop;
use const Amp\File\LOOP_STATE_IDENTIFIER;
use function Test\Support\proxy;

abstract class FileInfoTestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // prefer eio driver over uv due to uv crashes
        if (\extension_loaded('eio')) {
            $driver = new EioDriver();

            Loop::setState(LOOP_STATE_IDENTIFIER, $driver);
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // clear amp stat cache
        StatCache::clear();

        // clear native stat cache
        clearstatcache(true);
    }

    public function fileOrDirectoryPathProvider(): array
    {
        return [
            'testSubDirectory'          => [__DIR__],
            'testFile'                  => [__FILE__],
            'testFileDotLinks'          => [substr(__FILE__, strlen(realpath(__DIR__ . '/../..')) + 1)],
            'testRootDirectory'         => ['/'],
            'testNonExistingPath'       => [uniqid() . '/abc/123'],
            'testExtraSlashes'          => ['vendor////composer//installed.json'],
            'testRelativeFileDepthZero' => ['composer.json'],
            'testRelativeFileDepthOne'  => ['vendor/autoload.php'],
            'testRelativeFileDotLinks'  => ['vendor/../autoload.json'],
            'testDirectoryWithPeriod'   => ['.idea'],
        ];
    }

    public function linkPathProvider(): array
    {
        return [
            'testSubDirectory'    => [__DIR__],
            'testFile'            => [__FILE__],
            'testRootDirectory'   => ['/'],
            'testNonExistingPath' => [uniqid() . '/abc/123'],
        ];
    }

    /**
     * @dataProvider fileOrDirectoryPathProvider
     *
     * @param string $pathname
     */
    public function testGettersFileOrDirectoryPath(string $pathname): void
    {
        $this->assertGettersSame($pathname);
    }

    /**
     * @dataProvider linkPathProvider
     *
     * @param string $pathname
     */
    public function testGettersLinkPath(string $pathname): void
    {
        $link = sys_get_temp_dir() . '/denimsoft_file_' . uniqid();
        symlink($pathname, $link);

        try {
            $this->assertGettersSame($link);
        } finally {
            if (file_exists($link)) {
                @unlink($link);
            }
        }
    }

    protected function extract(\SplFileInfo $fileInfo)
    {
        /** @var \SplFileInfo $proxy */
        $proxy = proxy($fileInfo);

        return [
            'atime'       => $proxy->getATime(),
            'basename'    => $proxy->getBasename(),
            'ctime'       => $proxy->getCTime(),
            'dir'         => $proxy->isDir(),
            'executable'  => $proxy->isExecutable(),
            'extension'   => $proxy->getExtension(),
            'file'        => $proxy->isFile(),
            'filename'    => $proxy->getFilename(),
            'group'       => $proxy->getGroup(),
            'inode'       => $proxy->getInode(),
            'link'        => $proxy->isLink(),
            'link_target' => $proxy->getLinkTarget(),
            'mtime'       => $proxy->getMTime(),
            'owner'       => $proxy->getOwner(),
            'path'        => $proxy->getPath(),
            'pathname'    => $proxy->getPathname(),
            'perms'       => $proxy->getPerms(),
            //            'readable'    => $proxy->isReadable(),
            'real_path' => $proxy->getRealPath(),
            'size'      => $proxy->getSize(),
            'type'      => $proxy->getType(),
            //            'writable'    => $proxy->isWritable(),
        ];
    }

    abstract protected function getTestFileInfoData(string $pathname): array;

    private function assertGettersSame(string $pathname): void
    {
        $expected = $this->extract(new \SplFileInfo($pathname));
        $actual   = $this->getTestFileInfoData($pathname);

        $this->assertSame($expected, $actual);
    }
}
