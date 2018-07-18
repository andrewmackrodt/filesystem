<?php

declare(strict_types=1);

namespace Test\Functional;

use Amp\File\EioDriver;
use Amp\File\StatCache;
use Amp\Loop;
use Denimsoft\File\AsyncFileInfo;
use const Amp\File\LOOP_STATE_IDENTIFIER;
use function Test\Support\proxy;

abstract class FileInfoTestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();

        // prefer eio driver over uv due to uv crashes
        if (\extension_loaded('eio')) {
            $driver = new EioDriver();

            Loop::setState(LOOP_STATE_IDENTIFIER, $driver);
        }
    }

    public function tearDown()
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
    public function testGettersFileOrDirectoryPath(string $pathname)
    {
        $this->assertGettersSame($pathname);
    }

    /**
     * @dataProvider linkPathProvider
     *
     * @param string $pathname
     */
    public function testGettersLinkPath(string $pathname)
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

    protected function extractAsyncFileInfo(AsyncFileInfo $fileInfo)
    {
        /** @var AsyncFileInfo $proxy */
        $proxy = proxy($fileInfo);

        return [
            'atime'       => $proxy->atime(),
            'basename'    => $proxy->basename(),
            'ctime'       => $proxy->ctime(),
            'dir'         => $proxy->dir(),
            'executable'  => $proxy->executable(),
            'extension'   => $proxy->extension(),
            'file'        => $proxy->file(),
            'filename'    => $proxy->filename(),
            'group'       => $proxy->group(),
            'inode'       => $proxy->inode(),
            'link'        => $proxy->link(),
            'link_target' => $proxy->linktarget(),
            'mtime'       => $proxy->mtime(),
            'owner'       => $proxy->owner(),
            'path'        => $proxy->path(),
            'pathname'    => $proxy->pathname(),
            'perms'       => $proxy->permissions(),
            'readable'    => $proxy->readable(),
            'real_path'   => $proxy->realpath(),
            'size'        => $proxy->size(),
            'type'        => $proxy->type(),
            'writable'    => $proxy->writable(),
        ];
    }

    protected function extractSplFileInfo(\SplFileInfo $fileInfo)
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
            'readable'    => $proxy->isReadable(),
            'real_path'   => $proxy->getRealPath(),
            'size'        => $proxy->getSize(),
            'type'        => $proxy->getType(),
            'writable'    => $proxy->isWritable(),
        ];
    }

    abstract protected function getTestFileInfoData(string $pathname): array;

    private function assertGettersSame(string $pathname)
    {
        $expected = $this->extractSplFileInfo(new \SplFileInfo($pathname));
        $actual   = $this->getTestFileInfoData($pathname);

        $this->assertSame($expected, $actual);
    }
}
