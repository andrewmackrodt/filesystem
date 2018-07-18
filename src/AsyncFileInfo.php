<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Failure;
use Amp\File\Driver as FileDriver;
use Amp\Promise;
use Amp\Success;
use function Amp\call;
use function Amp\Promise\all;
use function Amp\Promise\any;

/**
 * Asynchronous implementation of SplFileInfo, all public methods MUST return a Promise.
 */
class AsyncFileInfo extends \SplFileInfo
{
    /**
     * @var FileDriver
     */
    private $fileDriver;

    /**
     * @var string
     */
    private $pathname;

    public function __construct(string $pathname, FileDriver $fileDriver)
    {
        parent::__construct($pathname);

        $this->pathname   = $pathname;
        $this->fileDriver = $fileDriver;
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getATime(): Promise
    {
        return $this->statAttr(__METHOD__, 'atime');
    }

    /**
     * @param string|null $suffix
     *
     * @return Promise|string
     */
    public function getBasename($suffix = null): Promise
    {
        return new Success(parent::getBasename(...func_get_args()));
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getCTime(): Promise
    {
        return $this->statAttr(__METHOD__, 'ctime');
    }

    /**
     * @return Promise|string
     */
    public function getExtension(): Promise
    {
        return new Success(parent::getExtension());
    }

    /**
     * @param string|null $className
     *
     * @throws \BadMethodCallException Always throws "Not Supported"
     *
     * @return Promise|\SplFileInfo
     */
    public function getFileInfo($className = null): Promise
    {
        return new Failure(new \BadMethodCallException('Not Supported'));
    }

    /**
     * @return Promise|string
     */
    public function getFilename(): Promise
    {
        return new Success(parent::getFilename());
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getGroup(): Promise
    {
        return $this->statAttr(__METHOD__, 'gid');
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getInode(): Promise
    {
        return $this->statAttr(__METHOD__, 'ino');
    }

    /**
     * @throws \RuntimeException if the file does not exist or is not a link
     *
     * @return Promise|string
     */
    public function getLinkTarget(): Promise
    {
        return call(function () {
            if ( ! ($target = yield $this->_getLinkTarget())) {
                $n = yield all([$this->isLink(), $this->fileDriver->stat($this->pathname)]);

                return new Failure(new \RuntimeException(sprintf(
                    'Unable to read link %s, error: %s',
                    $this->pathname,
                    $n[1] ? 'Invalid argument' : 'No such file or directory'
                )));
            }

            return $target;
        });
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getMTime(): Promise
    {
        return $this->statAttr(__METHOD__, 'mtime');
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getOwner(): Promise
    {
        return $this->statAttr(__METHOD__, 'uid');
    }

    /**
     * @return Promise|string
     */
    public function getPath(): Promise
    {
        return new Success(parent::getPath());
    }

    /**
     * @param string|null $className
     *
     * @throws \InvalidArgumentException if className is not NULL
     *
     * @return Promise|\SplFileInfo
     */
    public function getPathInfo($className = null): Promise
    {
        if ($className !== null) {
            return new Failure(new \InvalidArgumentException('className is not supported'));
        }

        return new Success(new self(parent::getPath(), $this->fileDriver));
    }

    /**
     * @return Promise|string
     */
    public function getPathname(): Promise
    {
        return new Success($this->pathname);
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getPerms(): Promise
    {
        return $this->statAttr(__METHOD__, 'mode');
    }

    /**
     * @return Promise|string|false
     */
    public function getRealPath(): Promise
    {
        return call(function () {
            if ( ! yield $this->fileDriver->exists($this->pathname)) {
                return false;
            }

            if (yield $this->isLink()) {
                return $this->getLinkTarget();
            }

            return $this->pathname[0] === '/'
                ? normalizePath($this->pathname)
                : normalizePath(getcwd() . DIRECTORY_SEPARATOR . $this->pathname);
        });
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getSize()
    {
        return $this->statAttr(__METHOD__, 'size');
    }

    /**
     * @return Promise|string|false
     */
    public function getType(): Promise
    {
        return call(function () {
            $lstat = yield $this->lstat('SplFileInfo::getType');

            if ( ! $lstat) {
                return false;
            }

            if (($isLink = $this->isLink()) instanceof Promise) {
                $isLink = yield $isLink;
            }

            if ($isLink) {
                return 'link';
            }

            return (yield $this->isDir()) ? 'dir' : 'file';
        });
    }

    public function isDir(): Promise
    {
        return $this->tryCallStat(function (array $stat) {
            return decoct($stat['mode'])[0] === '4';
        });
    }

    public function isExecutable(): Promise
    {
        return new Success(\is_executable($this->pathname));
    }

    public function isFile(): Promise
    {
        return $this->tryCallStat(function (array $stat) {
            return decoct($stat['mode'])[0] !== '4';
        });
    }

    public function isLink(): Promise
    {
        return call(function () {
            if ( ! ($lstat = yield $this->fileDriver->lstat($this->pathname))) {
                return false;
            }

            $stat = yield $this->fileDriver->stat($this->pathname);

            return [$stat['dev'], $stat['ino']] !== [$lstat['dev'], $lstat['ino']];
        });
    }

    public function isReadable(): Promise
    {
        return new Success(\is_readable($this->pathname));
    }

    public function isWritable(): Promise
    {
        return new Success(\is_writable($this->pathname));
    }

    /**
     * @param string $openMode
     * @param bool   $useIncludePath
     * @param null   $context
     *
     * @throws \InvalidArgumentException if useIncludePath is not FALSE
     * @throws \InvalidArgumentException if context is not NULL
     *
     * @return Promise|\SplFileObject
     */
    public function openFile($openMode = 'r', $useIncludePath = false, $context = null): Promise
    {
        if ($useIncludePath !== false) {
            return new Failure(new \InvalidArgumentException('useIncludePath is not supported'));
        }

        if ($context !== null) {
            return new Failure(new \InvalidArgumentException('context is not supported'));
        }

        return $this->fileDriver->open($this->pathname, $openMode);
    }

    /**
     * @param string|null $className
     *
     * @throws \BadMethodCallException Always throws "Not Supported"
     *
     * @return Promise
     */
    public function setFileClass($className = null): Promise
    {
        return new Failure(new \BadMethodCallException('Not Supported'));
    }

    /**
     * @param string|null $className
     *
     * @throws \BadMethodCallException Always throws "Not Supported"
     *
     * @return Promise
     */
    public function setInfoClass($className = null): Promise
    {
        return new Failure(new \BadMethodCallException('Not Supported'));
    }

    /**
     * @return Promise|ImmutableFileInfo
     */
    public function toImmutable(): Promise
    {
        return call(function () {
            $yielded = yield any([
                $this->fileDriver->stat($this->pathname),
                $this->_getLinkTarget(),
                $this->isReadable(),
                $this->isWritable(),
                $this->isExecutable(),
            ]);

            $results = $yielded[1];
            $stat = $results[0] ?? [];
            $target = $results[1] ?? null;
            $readable = $results[2] ?? false;
            $writable = $results[3] ?? false;
            $executable = $results[4] ?? false;

            return new ImmutableFileInfo($this->pathname, getcwd(), $stat, $target, $readable, $writable, $executable);
        });
    }

    /**
     * @return Promise|string|null
     */
    private function _getLinkTarget(): Promise
    {
        return call(function () {
            // check if link first to avoid crash in extension
            if ( ! yield $this->isLink()) {
                return null;
            }

            return $this->fileDriver->readlink($this->pathname);
        });
    }

    /**
     * @param string $caller
     *
     * @throws \RuntimeException if the file does not exist
     *
     * @return Promise|array
     */
    private function lstat(string $caller): Promise
    {
        return call(function () use ($caller) {
            if ( ! ($lstat = yield $this->fileDriver->lstat($this->pathname))) {
                return new Failure(new \RuntimeException(sprintf(
                    '%s(): Lstat failed for %s',
                    preg_replace('#^.+::#', 'SplFileInfo::', $caller),
                    $this->pathname
                )));
            }

            return $lstat;
        });
    }

    /**
     * @param string $caller
     * @param string $key
     *
     * @return Promise|int
     */
    private function statAttr(string $caller, string $key): Promise
    {
        return call(function () use ($caller, $key) {
            if ( ! ($stat = yield $this->fileDriver->stat($this->pathname))) {
                return new Failure(new \RuntimeException(sprintf(
                    '%s(): stat failed for %s',
                    preg_replace('#^.+::#', 'SplFileInfo::', $caller),
                    $this->pathname
                )));
            }

            return $stat[$key];
        });
    }

    private function tryCallStat(callable $callable)
    {
        return call(function () use ($callable) {
            if ( ! ($stat = yield $this->fileDriver->stat($this->pathname))) {
                return false;
            }

            return $callable($stat);
        });
    }
}
