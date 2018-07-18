<?php

declare(strict_types=1);

namespace Test\Support {
    function failedThrowableToArray(array $array): array
    {
        return array_map(
            function (\Throwable $t) {
                return throwableToArray($t);
            },
            $array
        );
    }

    function throwableToArray(\Throwable $t): array
    {
        return [
            'message'   => strtolower(preg_replace('/^.+?: /', '', $t->getMessage())),
            'code'      => $t->getCode(),
            'exception' => get_class($t),
        ];
    }

    function proxy($object)
    {
        return new class($object) {
            private $object;

            public function __construct($object)
            {
                $this->object = $object;
            }

            public function __call(string $name, array $arguments)
            {
                try {
                    return $this->object->$name(...$arguments);
                } catch (\Throwable $t) {
                    return throwableToArray($t);
                }
            }
        };
    }
}
