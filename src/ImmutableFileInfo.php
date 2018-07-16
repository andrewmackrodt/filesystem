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
     * @var string
     */
    private $pathname;

    /**
     * @var array|null
     */
    private $stat;
    /**
     * @var string|null
     */
    private $target;

    public function __construct(
        string $pathname,
        string $cwd,
        array $stat = null,
        string $target = null
    ) {
        // no call to parent constructor

        if (isset($stat['nlink'])) {
            $stat['link'] = $stat['nlink'];

            unset($stat['nlink']);
        }

        $this->pathname = $pathname;
        $this->stat     = $stat;
        $this->cwd      = $cwd;
        $this->target   = $target;
    }

    public function getATime(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['atime'];
    }

    public function getBasename($suffix = null): string
    {
        $basename = \basename($this->pathname);

        if ($suffix !== null) {
            $length = strlen($suffix);

            if (substr($basename, -$length) === $suffix) {
                $basename = substr($basename, 0, -$length);
            }
        }

        return $basename;
    }

    public function getCTime(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['ctime'];
    }

    public function getExtension(): string
    {
        $filename = \basename($this->pathname);

        if (($pos = strrpos($filename, '.')) === false) {
            return '';
        }

        return substr($filename, $pos + 1);
    }

    public function getFileInfo($className = null): \SplFileInfo
    {
        throw new \BadMethodCallException('Not Supported');
    }

    public function getFilename(): string
    {
        return \basename($this->pathname) ?: $this->pathname;
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

    public function getPath(): string
    {
        if (($pos = strrpos($this->pathname, DIRECTORY_SEPARATOR)) !== false) {
            return substr($this->pathname, 0, $pos);
        }

        return '';
    }

    public function getPathInfo($className = null): \SplFileInfo
    {
        throw new \BadMethodCallException('Not Supported');
    }

    public function getPathname(): string
    {
        return $this->pathname;
    }

    public function getPerms(): int
    {
        $this->assertStatNotEmpty(__METHOD__);

        return $this->stat['mode'];
    }

    public function getRealPath()
    {
        if (empty($this->stat)) {
            return false;
        }

        if ($this->target) {
            return normalizePath($this->target);
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

        switch ($this->stat['link']) {
            case 1:
                return 'file';
            default:
                return 'dir';
        }
    }

    public function isDir(): bool
    {
        if (empty($this->stat)) {
            return false;
        }

        return $this->stat['link'] !== 1;
    }

    public function isExecutable(): bool
    {
        if (empty($this->stat)) {
            return false;
        }

        $octal = substr(decoct($this->stat['mode']), -3);

        return (bool) preg_match('/[1357]/', $octal);
    }

    public function isFile(): bool
    {
        if (empty($this->stat)) {
            return false;
        }

        return $this->stat['link'] === 1;
    }

    public function isLink(): bool
    {
        return (bool) $this->target;
    }

    public function isReadable(): bool
    {
        throw new \BadMethodCallException('Not Supported');
    }

    public function isWritable(): bool
    {
        throw new \BadMethodCallException('Not Supported');
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
        throw new \BadMethodCallException('Not Supported');
    }

    public function setFileClass($className = null): void
    {
        throw new \BadMethodCallException('Not Supported');
    }

    public function setInfoClass($className = null): void
    {
        throw new \BadMethodCallException('Not Supported');
    }

    private function assertStatNotEmpty(string $callerName, bool $lstat = false): void
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
