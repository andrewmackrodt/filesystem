<?php

declare(strict_types=1);

namespace Test\Functional;

use Amp\Loop;
use Amp\Promise;
use Denimsoft\File\Directory;
use Denimsoft\File\File;
use Denimsoft\File\Filesystem;
use function Amp\File\driver;
use function Amp\Promise\any;
use function Test\Support\failedThrowableToArray;

class NodeTest extends NodeTestCase
{
    protected function getTestFileInfoData(string $pathname): array
    {
        $filesystem = new Filesystem(driver());

        $node = is_dir($pathname)
            ? new Directory($pathname, $filesystem)
            : new File($pathname, $filesystem);

        $promises = $this->extractAsyncFileInfo($node);
        $this->assertArrayOf(Promise::class, $promises);

        // resolve promises
        $data = [];
        Loop::run(function () use ($promises, &$data) {
            list($failed, $succeeded) = yield any($promises);
            if ( ! empty($succeeded['link_target'])) {
                $succeeded['link_target'] = yield $succeeded['link_target']->pathname();
            }
            $data = failedThrowableToArray($failed) + $succeeded;
            ksort($data);
        });

        return $data;
    }

    private function assertArrayOf($expectedType, array $array)
    {
        $typeErrors = array_filter($array, function ($value) use ($expectedType) {
            return ! $value instanceof $expectedType;
        });

        $message = sprintf(
            'One or more methods did not return an instance of %s: %s',
            $expectedType,
            implode(', ', array_keys($typeErrors))
        );

        $this->assertCount(0, $typeErrors, $message);
    }
}
