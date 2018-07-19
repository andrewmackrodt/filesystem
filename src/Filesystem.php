<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Deferred;
use Amp\File\Driver;
use Amp\Promise;
use Amp\Success;
use function Amp\File\filesystem;

class Filesystem
{
    /**
     * @var DirectoryScanner
     */
    private $directoryScanner;

    /**
     * @var Driver
     */
    private $driver;

    public function __construct(Driver $driver = null)
    {
        $this->driver           = $driver ?? filesystem();
        $this->directoryScanner = new DirectoryScanner($this, $this->driver);
    }

    /**
     * Retrieve the path's last access time as a unix timestamp.
     *
     * @param string $path An absolute file system path
     *
     * @return \Amp\Promise<int>
     */
    public function atime(string $path): Promise
    {
        return $this->driver->atime(...func_get_args());
    }

    /**
     * chmod a file or directory.
     *
     * @param string $path
     * @param int    $mode
     *
     * @return \Amp\Promise
     */
    public function chmod(string $path, int $mode): Promise
    {
        return $this->driver->chmod(...func_get_args());
    }

    /**
     * chown a file or directory.
     *
     * @param string $path
     * @param int    $uid
     * @param int    $gid
     *
     * @return \Amp\Promise
     */
    public function chown(string $path, int $uid, int $gid): Promise
    {
        return $this->driver->chown(...func_get_args());
    }

    /**
     * Retrieve the path's creation time as a unix timestamp.
     *
     * @param string $path An absolute file system path
     *
     * @return \Amp\Promise<int>
     */
    public function ctime(string $path): Promise
    {
        return $this->driver->ctime(...func_get_args());
    }

    /**
     * Does the specified path exist?
     *
     * This function should never resolve as a failure -- only a successfull bool value
     * indicating the existence of the specified path.
     *
     * @param string $path An absolute file system path
     *
     * @return \Amp\Promise<bool>
     */
    public function exists(string $path): Promise
    {
        return $this->driver->exists(...func_get_args());
    }

    /**
     * @param string $path
     *
     * @return \Amp\Promise<File|Directory>
     */
    public function fileinfo(string $path): Promise
    {
        $deferred = new Deferred();

        $this->driver->isdir($path)
            ->onResolve(function ($error, $result) use ($path, $deferred) {
                if ($result) {
                    $node = new Directory($path, $this);
                } else {
                    $node = new File($path, $this);
                }

                $deferred->resolve($node);
            })
        ;

        return $deferred->promise();
    }

    /**
     * Buffer the specified file's contents.
     *
     * @param string $path The file path from which to buffer contents
     *
     * @return \Amp\Promise A promise resolving to a string upon successful resolution
     */
    public function get(string $path): Promise
    {
        return $this->driver->get(...func_get_args());
    }

    /**
     * Does the specified path exist and is it a directory?
     *
     * If the path does not exist the returned Promise will resolve
     * to FALSE and will not reject with an error.
     *
     * @param string $path An absolute file system path
     *
     * @return \Amp\Promise<bool>
     */
    public function isdir(string $path): Promise
    {
        return $this->driver->isdir(...func_get_args());
    }

    /**
     * Does the specified path exist and is it a file?
     *
     * If the path does not exist the returned Promise will resolve
     * to FALSE and will not reject with an error.
     *
     * @param string $path An absolute file system path
     *
     * @return \Amp\Promise<bool>
     */
    public function isfile(string $path): Promise
    {
        return $this->driver->isfile(...func_get_args());
    }

    /**
     * Create a hard link $link pointing to the file/directory located at $target.
     *
     * @param string $target
     * @param string $link
     *
     * @return \Amp\Promise
     */
    public function link(string $target, string $link): Promise
    {
        return $this->driver->link(...func_get_args());
    }

    /**
     * Same as stat() except if the path is a link then the link's data is returned.
     *
     * @param string $path The file system path to stat
     *
     * @return \Amp\Promise A promise resolving to an associative array upon successful resolution
     */
    public function lstat(string $path): Promise
    {
        return $this->driver->lstat(...func_get_args());
    }

    /**
     * Create a director.
     *
     * @param string $path
     * @param int    $mode
     * @param bool   $recursive
     *
     * @return \Amp\Promise
     */
    public function mkdir(string $path, int $mode = 0777, bool $recursive = false): Promise
    {
        return $this->driver->mkdir(...func_get_args());
    }

    /**
     * Retrieve the path's last modification time as a unix timestamp.
     *
     * @param string $path An absolute file system path
     *
     * @return \Amp\Promise<int>
     */
    public function mtime(string $path): Promise
    {
        return $this->driver->mtime(...func_get_args());
    }

    /**
     * Open a handle for the specified path.
     *
     * @param string $path
     * @param string $mode
     *
     * @return \Amp\Promise<\Amp\File\Handle>
     */
    public function open(string $path, string $mode): Promise
    {
        return $this->driver->open(...func_get_args());
    }

    /**
     * Write the contents string to the specified path.
     *
     * @param string $path     The file path to which to $contents should be written
     * @param string $contents The data to write to the specified $path
     *
     * @return \Amp\Promise A promise resolving to the integer length written upon success
     */
    public function put(string $path, string $contents): Promise
    {
        return $this->driver->put(...func_get_args());
    }

    /**
     * Read the symlink at $path.
     *
     * @param string $target
     *
     * @return \Amp\Promise
     */
    public function readlink(string $target): Promise
    {
        return $this->driver->readlink(...func_get_args());
    }

    /**
     * Rename a file or directory.
     *
     * @param string $from
     * @param string $to
     *
     * @return \Amp\Promise
     */
    public function rename(string $from, string $to): Promise
    {
        return $this->driver->rename(...func_get_args());
    }

    /**
     * Delete a directory.
     *
     * @param string $path
     *
     * @return \Amp\Promise
     */
    public function rmdir(string $path): Promise
    {
        return $this->driver->rmdir(...func_get_args());
    }

    /**
     * Retrieve an array of AsyncFileInfo representing files and directories
     * inside the specified path.
     *
     * @param string $path
     * @param bool   $recursive
     *
     * @return \Amp\Promise<AsyncFileInfo[]>
     */
    public function scandir(string $path, bool $recursive = false): Promise
    {
        return $this->directoryScanner->listFiles($path, $recursive);
    }

    /**
     * Retrieve the size in bytes of the file at the specified path.
     *
     * If the path does not exist or is not a regular file this
     * function's returned Promise WILL resolve as a failure.
     *
     * @param string $path An absolute file system path
     *
     * @return \Amp\Promise<int>
     */
    public function size(string $path): Promise
    {
        return $this->driver->size(...func_get_args());
    }

    /**
     * Execute a file stat operation.
     *
     * If the requested path does not exist the resulting Promise will resolve to NULL.
     *
     * @param string $path The file system path to stat
     *
     * @return \Amp\Promise<array|null>
     */
    public function stat(string $path): Promise
    {
        return $this->driver->stat(...func_get_args());
    }

    /**
     * Create a symlink $link pointing to the file/directory located at $target.
     *
     * @param string $target
     * @param string $link
     *
     * @return \Amp\Promise
     */
    public function symlink(string $target, string $link): Promise
    {
        return $this->driver->symlink(...func_get_args());
    }

    /**
     * Update the access and modification time of the specified path.
     *
     * If the file does not exist it will be created automatically.
     *
     * @param string $path
     * @param int    $time  The touch time. If $time is not supplied, the current system time is used.
     * @param int    $atime The access time. If $atime is not supplied, value passed to the $time parameter is used.
     *
     * @return \Amp\Promise
     */
    public function touch(string $path, int $time = null, int $atime = null): Promise
    {
        return $this->driver->touch(...func_get_args());
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return \Amp\Promise
     */
    public function unlink(string $path): Promise
    {
        return $this->driver->unlink(...func_get_args());
    }
}
