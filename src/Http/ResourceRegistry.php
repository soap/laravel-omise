<?php

namespace Soap\LaravelOmise\Http;

use ArrayAccess;
use ReflectionClass;

/**
 * Stands in for the instance registry of `OmiseApiResource`.
 *
 * The SDK replaces its HTTP executor with a new `OmiseHttpExecutor` the first time
 * each resource class is used. Reporting every resource as already registered keeps
 * the executor installed by `Transport`.
 *
 * @implements ArrayAccess<class-string, object>
 */
class ResourceRegistry implements ArrayAccess
{
    /**
     * @var array<class-string, object>
     */
    protected $instances = [];

    public function offsetExists($offset): bool
    {
        return true;
    }

    /**
     * @param  class-string  $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->instances[$offset] ??= (new ReflectionClass($offset))->newInstanceWithoutConstructor();
    }

    public function offsetSet($offset, $value): void
    {
        $this->instances[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->instances[$offset]);
    }
}
