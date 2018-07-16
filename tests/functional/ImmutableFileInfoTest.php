<?php

declare(strict_types=1);

namespace Test\Functional;

use Amp\Loop;
use Denimsoft\File\ImmutableFileInfo;
use function Amp\File\driver;

class ImmutableFileInfoTest extends FileInfoTestCase
{
    protected function getTestFileInfoData(string $pathname): array
    {
        $data = [];
        Loop::run(function () use ($pathname, &$data) {
            $fileStat = yield driver()->stat($pathname);
            $linkStat = yield driver()->lstat($pathname);
            $linkTarget = null;

            if ([$linkStat['dev'], $linkStat['ino']] !== [$fileStat['dev'], $fileStat['ino']]) {
                $linkTarget = yield driver()->readlink($pathname);
            }

            $fileInfo = new ImmutableFileInfo($pathname, getcwd(), $fileStat, $linkTarget);
            $data = $this->extract($fileInfo);
        });

        return $data;
    }
}
