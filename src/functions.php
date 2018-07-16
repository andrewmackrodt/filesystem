<?php

declare(strict_types=1);

namespace Denimsoft\File {
    function normalizePath(string $path): string
    {
        $path = preg_replace('#/{2,}#', '/', $path);

        if ($path === '/') {
            return $path;
        }

        $paths = [];

        foreach (explode('/', $path) as $segment) {
            // move up a directory
            if ($segment === '..') {
                if (count($paths) < 1) {
                    throw new \InvalidArgumentException("Illegal path: $path");
                }
                array_pop($paths);
                continue;
            }
            // ignore this folder
            if ($segment === '.') {
                continue;
            }
            $paths[] = $segment;
        }

        return implode('/', $paths);
    }
}
