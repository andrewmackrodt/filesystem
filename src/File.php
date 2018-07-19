<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Promise;
use Amp\Success;
use function Amp\call;

/**
 * @property-read string $extension
 */
class File extends Node
{
    public function __get(string $name)
    {
        if ($name === 'extension') {
            $filename = basename($this->pathname);

            if (($pos = strrpos($filename, '.')) === false) {
                return '';
            }

            return substr($filename, $pos + 1);
        }

        return parent::__get($name);
    }

    /**
     * @return \Amp\Promise<bool>
     */
    public function dir(): Promise
    {
        return $this->statOrFail(function (array $stat) {
            return decoct($stat['mode'])[0] === '4';
        });
    }

    /**
     * @return \Amp\Promise<string>
     */
    public function extension(): Promise
    {
        return new Success($this->extension);
    }

    /**
     * @return \Amp\Promise<bool>
     */
    public function file(): Promise
    {
        return $this->statOrFail(function (array $stat) {
            return decoct($stat['mode'])[0] !== '4';
        });
    }

    /**
     * @param string $mode
     *
     * @return \Amp\Promise<\Amp\File\Handle>
     */
    public function open($mode = 'r'): Promise
    {
        return $this->filesystem->open($this->pathname, $mode);
    }

    private function statOrFail(callable $callable)
    {
        return call(function () use ($callable) {
            if ( ! ($stat = yield $this->filesystem->stat($this->pathname))) {
                return false;
            }

            return $callable($stat);
        });
    }
}
