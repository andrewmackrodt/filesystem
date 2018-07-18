<?php

declare(strict_types=1);

namespace Denimsoft\File;

class ImmutableFileInfo extends \SplFileInfo
{
    /**
     * @var string
     */
    private $cwd;

    /**
     * @var bool
     */
    private $executable;

    /**
     * @var string
     */
    private $pathname;

    /**
     * @var bool
     */
    private $readable;

    /**
     * @var array|null
     */
    private $stat;
    /**
     * @var string|null
     */
    private $target;

    /**
     * @var bool
     */
    private $writable;

    public function __construct(
        string $pathname,
        string $cwd,
        array $stat = null,
        string $target = null,
        bool $readable = false,
        bool $writable = false,
        bool $executable = false
    ) {
        parent::__construct($pathname);

        $this->pathname   = $pathname;
        $this->stat       = $stat;
        $this->cwd        = $cwd;
        $this->target     = $target;
        $this->readable   = $readable;
        $this->writable   = $writable;
        $this->executable = $executable;
    }

    public function getATime(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['atime'];
    }

    public function getCTime(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['ctime'];
    }

    public function getFileInfo($className = null): \SplFileInfo
    {
        throw new \BadMethodCallException('Not Supported');
    }

    public function getGroup(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['gid'];
    }

    public function getInode(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['ino'];
    }

    public function getLinkTarget(): string
    {
        if ( ! $this->target) {
            throw new \RuntimeException(sprintf(
                'Unable to read link %s, error: %s',
                $this->pathname,
                empty($this->stat) ? 'No such file or directory' : 'Invalid argument'
            ));
        }

        return $this->target;
    }

    public function getMTime(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['mtime'];
    }

    public function getOwner(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['uid'];
    }

    /**
     * @param string|null$className
     *
     * @throws \BadMethodCallException Always throws "Not Supported"
     */
    public function getPathInfo($className = null)
    {
        throw new \BadMethodCallException('Not Supported');
    }

    public function getPerms(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['mode'];
    }

    public function getRealPath()
    {
        if ( ! $this->stat) {
            return false;
        }

        if ($this->target) {
            return $this->target;
        }

        return $this->pathname[0] === '/'
            ? normalizePath($this->pathname)
            : normalizePath($this->cwd . DIRECTORY_SEPARATOR . $this->pathname);
    }

    public function getSize(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['size'];
    }

    public function getType(): string
    {
        $this->assertStatNotEmpty(__METHOD__, true);

        if ($this->target !== null) {
            return 'link';
        }

        if ( ! $this->stat) {
            return 'file';
        }

        return decoct($this->stat['mode'])[0] === '4' ? 'dir' : 'file';
    }

    public function isDir(): bool
    {
        if ( ! $this->stat) {
            return false;
        }

        return decoct($this->stat['mode'])[0] === '4';
    }

    public function isExecutable(): bool
    {
        return $this->executable;
    }

    public function isFile(): bool
    {
        if ( ! $this->stat) {
            return false;
        }

        return decoct($this->stat['mode'])[0] !== '4';
    }

    public function isLink(): bool
    {
        return (bool) $this->target;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * @param string $openMode
     * @param bool   $useIncludePath
     * @param null   $context
     *
     * @throws \BadMethodCallException Always throws "Not Supported"
     */
    public function openFile($openMode = 'r', $useIncludePath = false, $context = null)
    {
        throw new \BadMethodCallException('Not Supported');
    }

    /**
     * @param string|null $className
     *
     * @throws \BadMethodCallException Always throws "Not Supported"
     */
    public function setFileClass($className = null)
    {
        throw new \BadMethodCallException('Not Supported');
    }

    /**
     * @param string|null $className
     *
     * @throws \BadMethodCallException Always throws "Not Supported"
     */
    public function setInfoClass($className = null)
    {
        throw new \BadMethodCallException('Not Supported');
    }

    private function assertStatNotEmpty(string $callerName, bool $lstat = false)
    {
        if ( ! empty($this->stat) || ($lstat && $this->target !== null)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            '%s(): %s failed for %s',
            preg_replace('#^.+::#', 'SplFileInfo::', $callerName),
            $lstat ? 'Lstat' : 'stat',
            $this->pathname
        ));
    }
}
