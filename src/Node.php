<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use function Amp\call;

/**
 * @property-read string         $basename
 * @property-read string|null    $extension
 * @property-read string         $filename
 * @property-read Directory|null $parent
 * @property-read string|null    $path
 * @property-read string         $pathname
 */
abstract class Node
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $pathname;

    public function __construct(string $pathname, Filesystem $filesystem)
    {
        $this->pathname   = $pathname;
        $this->filesystem = $filesystem;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'basename':
                return basename($this->pathname);

            case 'extension':
                $filename = basename($this->pathname);

                if (($pos = strrpos($filename, '.')) === false) {
                    return null;
                }

                return substr($filename, $pos + 1);

            case 'filename':
                return basename($this->pathname) ?: $this->pathname;

            case 'parent':
            case 'path':
                if (($pos = strrpos($this->pathname, DIRECTORY_SEPARATOR)) === false) {
                    return null;
                }

                if ( ! ($path = substr($this->pathname, 0, $pos))) {
                    return null;
                }

                if ($name === 'path') {
                    return $path;
                }

                return new Directory($path, $this->filesystem);

            case 'pathname':
                return $this->pathname;
        }

        throw new \InvalidArgumentException("Property $name does not exist");
    }

    /**
     * Returns the node last access time.
     *
     * @throws \RuntimeException if the node does not exist
     *
     * @return \Amp\Promise<int>
     */
    public function atime(): Promise
    {
        return $this->stat('atime');
    }

    /**
     * Returns the node basename.
     *
     * @return \Amp\Promise<string>
     */
    public function basename(): Promise
    {
        return new Success($this->basename);
    }

    /**
     * chmod the file or directory.
     *
     * @param int $mode
     *
     * @return \Amp\Promise
     */
    public function chmod(int $mode): Promise
    {
        return $this->filesystem->chmod($this->pathname, $mode);
    }

    /**
     * chown the file or directory.
     *
     * @param int $uid
     * @param int $gid
     *
     * @return \Amp\Promise
     */
    public function chown(int $uid, int $gid): Promise
    {
        return $this->filesystem->chown($this->pathname, $uid, $gid);
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return \Amp\Promise<int>
     */
    public function ctime(): Promise
    {
        return $this->stat('ctime');
    }

    /**
     * @return \Amp\Promise<bool>
     */
    public function exists(): Promise
    {
        return $this->filesystem->exists($this->pathname);
    }

    /**
     * @return \Amp\Promise<string>
     */
    public function extension(): Promise
    {
        return new Success($this->extension);
    }

    /**
     * @return \Amp\Promise<string>
     */
    public function filename(): Promise
    {
        return new Success($this->filename);
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return \Amp\Promise<int>
     */
    public function group(): Promise
    {
        return $this->stat('gid');
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return \Amp\Promise<int>
     */
    public function inode(): Promise
    {
        return $this->stat('ino');
    }

    /**
     * Note: this method is blocking.
     *
     * @return \Amp\Promise<bool>
     */
    public function isExecutable(): Promise
    {
        return new Success(\is_executable($this->pathname));
    }

    /**
     * Note: this method is blocking.
     *
     * @return \Amp\Promise<bool>
     */
    public function isReadable(): Promise
    {
        return new Success(\is_readable($this->pathname));
    }

    /**
     * Note: this method is blocking.
     *
     * @return \Amp\Promise<bool>
     */
    public function isWritable(): Promise
    {
        return new Success(\is_writable($this->pathname));
    }

    /**
     * @return \Amp\Promise<bool>
     */
    public function link(): Promise
    {
        return call(function () {
            if ( ! ($lstat = yield $this->filesystem->lstat($this->pathname))) {
                return false;
            }

            $stat = yield $this->filesystem->stat($this->pathname);

            return [$stat['dev'], $stat['ino']] !== [$lstat['dev'], $lstat['ino']];
        });
    }

    /**
     * @throws \RuntimeException if the file does not exist or is not a link
     *
     * @return \Amp\Promise<AsyncFileInfo>
     */
    public function linkTarget(): Promise
    {
        return call(function () {
            if ( ! ($target = yield $this->_getLinkTarget())) {
                $exists = yield $this->exists();

                return new Failure(new \RuntimeException(sprintf(
                    'Unable to read link %s, error: %s',
                    $this->pathname,
                    $exists ? 'Invalid argument' : 'No such file or directory'
                )));
            }

            return new static($target, $this->filesystem);
        });
    }

    /**
     * @param string|null $key
     *
     * @throws \RuntimeException if the file does not exist
     *
     * @return \Amp\Promise<array|int>
     */
    public function lstat(string $key = null): Promise
    {
        return call(function () use ($key) {
            if ( ! ($stat = yield $this->filesystem->lstat($this->pathname))) {
                return new Failure(new \RuntimeException("lstat failed for {$this->pathname}"));
            }

            if ($key !== null) {
                $stat = $stat[$key];
            }

            return $stat;
        });
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return \Amp\Promise<int>
     */
    public function mtime(): Promise
    {
        return $this->stat('mtime');
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return \Amp\Promise<int>
     */
    public function owner(): Promise
    {
        return $this->stat('uid');
    }

    /**
     * @return \Amp\Promise<Directory|null>
     */
    public function parent(): Promise
    {
        return new Success($this->parent);
    }

    /**
     * @return \Amp\Promise<string|null>
     */
    public function path(): Promise
    {
        return new Success($this->path);
    }

    /**
     * @return \Amp\Promise<string>
     */
    public function pathname(): Promise
    {
        return new Success($this->pathname);
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return \Amp\Promise<int>
     */
    public function permissions(): Promise
    {
        return $this->stat('mode');
    }

    /**
     * @return \Amp\Promise<string|false>
     */
    public function realpath(): Promise
    {
        return call(function () {
            if ( ! yield $this->exists()) {
                return false;
            }

            if (yield $this->link()) {
                /** @var self $target */
                $target = yield $this->linkTarget();

                return $target->pathname;
            }

            return $this->pathname[0] === '/'
                ? normalizePath($this->pathname)
                : normalizePath(getcwd() . DIRECTORY_SEPARATOR . $this->pathname);
        });
    }

    /**
     * @throws \RuntimeException If the file does not exist
     *
     * @return \Amp\Promise<int>
     */
    public function size()
    {
        return $this->stat('size');
    }

    /**
     * @param string|null $key
     *
     * @throws \RuntimeException if the file does not exist
     *
     * @return \Amp\Promise<array|int>
     */
    public function stat(string $key = null): Promise
    {
        return call(function () use ($key) {
            if ( ! ($stat = yield $this->filesystem->stat($this->pathname))) {
                return new Failure(new \RuntimeException("stat failed for {$this->pathname}"));
            }

            if ($key !== null) {
                $stat = $stat[$key];
            }

            return $stat;
        });
    }

    /**
     * @throws \RuntimeException if the file does not exist
     *
     * @return \Amp\Promise<string|false>
     */
    public function type(): Promise
    {
        return call(function () {
            if (yield $this->link()) {
                return 'link';
            }

            $stat = yield $this->filesystem->stat($this->pathname);

            if ( ! $stat) {
                yield $this->lstat();

                return false;
            }

            return $this->getNodeType();
        });
    }

    /**
     * @return string
     */
    abstract protected function getNodeType(): string;

    /**
     * @return \Amp\Promise<string|null>
     */
    private function _getLinkTarget(): Promise
    {
        return call(function () {
            // check if link first to avoid crash in extension
            if ( ! yield $this->link()) {
                return null;
            }

            return $this->filesystem->readlink($this->pathname);
        });
    }
}
