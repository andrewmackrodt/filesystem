<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Coroutine;
use Amp\Failure;
use Amp\File\Driver as FileDriver;
use Amp\Promise;
use Amp\Success;
use function Amp\call;
use function Amp\Promise\all;

/**
 * Asynchronous implementation of SplFileInfo, all public methods MUST return a Promise.
 */
class AsyncFileInfo extends \SplFileInfo
{
    /**
     * @var string|null
     */
    private $cwd;

    /**
     * @var string
     */
    private $fileClass = \SplFileObject::class;
    /**
     * @var FileDriver
     */
    private $fileDriver;

    /**
     * @var string
     */
    private $infoClass = self::class;

    /**
     * @var array|null
     */
    private $lstat;

    /**
     * @var int|null
     */
    private $lstatTime;
    /**
     * @var string
     */
    private $pathname;

    /**
     * @var array|null
     */
    private $stat;

    /**
     * @var int|null
     */
    private $statTime;

    /**
     * @var string|null
     */
    private $target;

    /**
     * @var int
     */
    private $ttl = 3;

    public function __construct(string $pathname, FileDriver $fileDriver)
    {
        // no call to parent constructor

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
        return new Coroutine($this->stat(__METHOD__, 'atime'));
    }

    /**
     * @param string|null $suffix
     *
     * @return Promise|string
     */
    public function getBasename($suffix = null): Promise
    {
        return new Success($this->_getBasename($suffix));
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getCTime(): Promise
    {
        return new Coroutine($this->stat(__METHOD__, 'ctime'));
    }

    /**
     * @return Promise|string
     */
    public function getExtension(): Promise
    {
        return new Success($this->_getExtension());
    }

    /**
     * @param string|null $className
     *
     * @return Promise|\SplFileInfo
     */
    public function getFileInfo($className = null): Promise
    {
        return new Success($this->_getFileInfo($className));
    }

    /**
     * @return Promise|string
     */
    public function getFilename(): Promise
    {
        return new Success($this->_getFilename());
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getGroup(): Promise
    {
        return new Coroutine($this->stat(__METHOD__, 'gid'));
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getInode(): Promise
    {
        return new Coroutine($this->stat(__METHOD__, 'ino'));
    }

    /**
     * @throws \RuntimeException if the file does not exist or is not a link
     *
     * @return Promise|string
     */
    public function getLinkTarget(): Promise
    {
        $this->invalidateCaches();

        if ($this->target !== null) {
            return $this->target;
        }

        return call(function () {
            if (($isLink = $this->isLink()) instanceof Promise) {
                $isLink = yield $isLink;
            }

            if ( ! $isLink) {
                return new Failure(new \RuntimeException(sprintf(
                    'Unable to read link %s, error: %s',
                    $this->pathname,
                    $this->stat ? 'Invalid argument' : 'No such file or directory'
                )));
            }

            $this->target = yield $this->fileDriver->readlink($this->pathname);

            return $this->target;
        });
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getMTime(): Promise
    {
        return new Coroutine($this->stat(__METHOD__, 'mtime'));
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getOwner(): Promise
    {
        return new Coroutine($this->stat(__METHOD__, 'uid'));
    }

    /**
     * @return Promise|string
     */
    public function getPath(): Promise
    {
        return new Success($this->_getPath());
    }

    /**
     * @param string|null $className
     *
     * @return Promise|\SplFileInfo
     */
    public function getPathInfo($className = null): Promise
    {
        return new Success($this->_getPathInfo($className));
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
        return new Coroutine($this->stat(__METHOD__, 'mode'));
    }

    /**
     * @return Promise|string|false
     */
    public function getRealPath(): Promise
    {
        return call(function () {
            if (($isLink = $this->isLink()) instanceof Promise) {
                $isLink = yield $isLink;
            }

            if ( ! $this->stat) {
                return false;
            }

            if ($isLink) {
                if (($linkTarget = $this->getLinkTarget()) instanceof Promise) {
                    $linkTarget = yield $linkTarget;
                }

                return normalizePath($linkTarget);
            }

            return $this->pathname[0] === '/'
                ? normalizePath($this->pathname)
                : normalizePath($this->cwd . DIRECTORY_SEPARATOR . $this->pathname);
        });
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return Promise|int
     */
    public function getSize()
    {
        return new Coroutine($this->stat(__METHOD__, 'size'));
    }

    /**
     * @return Promise|string|false
     */
    public function getType(): Promise
    {
        return call(function () {
            if (($lstat = $this->lstat('SplFileInfo::getType')) instanceof \Generator) {
                $lstat = yield new Coroutine($lstat);

                if ( ! $lstat) {
                    return false;
                }
            }

            if (($isLink = $this->isLink()) instanceof Promise) {
                $isLink = yield $isLink;
            }

            if ($isLink) {
                return 'link';
            }

            // stat link will be set after isLink has returned
            if ($this->stat['link'] === 1) {
                return 'file';
            }

            return 'dir';
        });
    }

    public function isDir(): Promise
    {
        return $this->callStat(function (array $stat) {
            return $stat['link'] !== 1;
        });
    }

    public function isExecutable(): Promise
    {
        return $this->callStat(function (array $stat) {
            $octal = substr(decoct($stat['mode']), -3);

            return (bool) preg_match('/[1357]/', $octal);
        });
    }

    public function isFile(): Promise
    {
        return $this->callStat(function (array $stat) {
            return $stat['link'] === 1;
        });
    }

    public function isLink(): Promise
    {
        return call(function () {
            // set stat and lstat
            if (($promise = $this->getUnresolvedStatPromise()) !== null) {
                yield $promise;
            }

            return [
                $this->lstat['dev'],
                $this->lstat['ino'],
            ] !== [
                $this->stat['dev'],
                $this->stat['ino'],
            ];
        });
    }

    public function isReadable(): Promise
    {
        //$this->fileDriver->open()

        return new Failure(new \RuntimeException('Not Implemented'));
    }

    public function isWritable(): Promise
    {
        return new Failure(new \RuntimeException('Not Implemented'));
    }

    /**
     * @param string $openMode
     * @param bool   $useIncludePath
     * @param null   $context
     *
     * @return \SplFileObject
     */
    public function openFile($openMode = 'r', $useIncludePath = false, $context = null)
    {
        return new Failure(new \BadMethodCallException('Not Supported'));
    }

    public function setFileClass($className = null): Promise
    {
        $this->fileClass = $className ?? \SplFileObject::class;

        return new Success();
    }

    public function setInfoClass($className = null): Promise
    {
        $this->infoClass = $className ?? self::class;

        return new Success();
    }

    private function _getBasename(string $suffix = null): string
    {
        $basename = basename($this->pathname);

        if ($suffix !== null) {
            $length = strlen($suffix);

            if (substr($basename, -$length) === $suffix) {
                $basename = substr($basename, 0, -$length);
            }
        }

        return $basename;
    }

    private function _getExtension(): string
    {
        $filename = basename($this->pathname);

        if (($pos = strrpos($filename, '.')) === false) {
            return '';
        }

        return substr($filename, $pos + 1);
    }

    private function _getFileInfo(string $className = null): \SplFileInfo
    {
        return $this->createFileInfo($this->pathname, $className);
    }

    private function _getFilename(): string
    {
        return basename($this->pathname) ?: $this->pathname;
    }

    private function _getPath(): string
    {
        $path = '';

        if (($pos = strrpos($this->pathname, DIRECTORY_SEPARATOR)) !== false) {
            $path = substr($this->pathname, 0, $pos);
        }

        return $path;
    }

    private function _getPathInfo(string $className = null): \SplFileInfo
    {
        return $this->createFileInfo($this->_getPath(), $className);
    }

    private function callStat(callable $callable)
    {
        return call(function () use ($callable) {
            if (($stat = $this->stat()) instanceof \Generator) {
                $stat = yield new Coroutine($stat);
            }

            if ( ! $stat) {
                return false;
            }

            return $callable($stat);
        });
    }

    private function createFileInfo(string $pathname, string $className = null): \SplFileInfo
    {
        if ( ! $className) {
            $className = $this->infoClass;
        }

        if (is_a($className, self::class, true)) {
            $pathInfo = new $className($pathname, $this->fileDriver);
        } else {
            $pathInfo = new $className($pathname);
        }

        return $pathInfo;
    }

    /**
     * @return Promise|null
     */
    private function getUnresolvedStatPromise()
    {
        $promises = [];

        foreach ([$this->lstat(), $this->stat()] as $stat) {
            if ($stat instanceof \Generator) {
                $promises[] = new Coroutine($stat);
            }
        }

        return $promises ? all($promises) : null;
    }

    private function invalidateCaches(): void
    {
        $now = microtime(true);
        $cwd = getcwd();

        if ($now - $this->statTime > $this->ttl
            || $now - $this->lstatTime > $this->ttl
            || ($this->pathname[0] === '/' && $cwd !== $this->cwd)
        ) {
            $this->stat      = null;
            $this->statTime  = null;
            $this->lstat     = null;
            $this->lstatTime = null;
            $this->cwd       = null;
            $this->target    = null;
        }
    }

    /**
     * @param string|null $caller
     * @param string|null $key
     *
     * @throws \RuntimeException if the file does not exist and caller is not null
     *
     * @return \Generator|int|null
     */
    private function lstat(string $caller = null, string $key = null): \Generator
    {
        $this->invalidateCaches();

        if ($this->lstat) {
            return $key ? $this->lstat[$key] : $this->lstat;
        }

        if ( ! ($lstat = yield $this->fileDriver->lstat($this->pathname))) {
            if ( ! $caller) {
                return null;
            }

            return new Failure(new \RuntimeException(sprintf(
                '%s(): Lstat failed for %s',
                preg_replace('#^.+::#', 'SplFileInfo::', $caller),
                $this->pathname
            )));
        }

        // some file drivers use nlink instead of link
        if (isset($lstat['nlink'])) {
            $lstat['link'] = $lstat['nlink'];

            unset($lstat['nlink']);
        }

        $this->lstatTime = microtime(true);

        $this->lstat = $lstat;

        return $key ? $lstat[$key] : $lstat;
    }

    /**
     * @param string|null $caller
     * @param string|null $key
     *
     * @throws \RuntimeException if the file does not exist and caller is not null
     *
     * @return \Generator|int|null
     */
    private function stat(string $caller = null, string $key = null): \Generator
    {
        $this->invalidateCaches();

        if ($this->stat) {
            return $key ? $this->stat[$key] : $this->stat;
        }

        if ( ! ($stat = yield $this->fileDriver->stat($this->pathname))) {
            if ( ! $caller) {
                return null;
            }

            return new Failure(new \RuntimeException(sprintf(
                '%s(): stat failed for %s',
                preg_replace('#^.+::#', 'SplFileInfo::', $caller),
                $this->pathname
            )));
        }

        // some file drivers use nlink instead of link
        if (isset($stat['nlink'])) {
            $stat['link'] = $stat['nlink'];

            unset($stat['nlink']);
        }

        $this->statTime = microtime(true);
        $this->cwd      = getcwd();

        $this->stat = $stat;

        return $key ? $stat[$key] : $stat;
    }
}
