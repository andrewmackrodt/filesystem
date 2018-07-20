<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Promise;

class File extends Node
{
    /**
     * @param string $mode
     *
     * @return \Amp\Promise<\Amp\File\Handle>
     */
    public function open($mode = 'r'): Promise
    {
        return $this->filesystem->open($this->pathname, $mode);
    }

    protected function getNodeType(): string
    {
        return 'file';
    }
}
