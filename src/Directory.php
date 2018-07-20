<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Promise;

class Directory extends Node
{
    /**
     * Retrieve an array of File|Directory inside the specified path.
     *
     * @param bool $recursive
     *
     * @return \Amp\Promise<Node[]>
     */
    public function children(bool $recursive = false): Promise
    {
        return $this->filesystem->scandir($this->pathname, $recursive);
    }

    protected function getNodeType(): string
    {
        return 'dir';
    }
}
