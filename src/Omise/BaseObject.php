<?php

namespace Soap\LaravelOmise\Omise;

use ArrayAccess;
use BadMethodCallException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;
use Soap\LaravelOmise\Events\RequestFailed;
use Soap\LaravelOmise\Exceptions\OmiseRequestException;

/**
 * @implements Arrayable<string, mixed>
 */
#[\AllowDynamicProperties]
class BaseObject implements Arrayable, JsonSerializable
{
    protected $object;

    /**
     * @param  mixed  $object  of \Soap\LaravelOmise\Omise\BaseObject.
     * @return $this
     */
    protected function refresh($object = null)
    {
        if ($this->object == null && $object == null) {
            return $this;
        }

        if ($object != null) {
            // The SDK reuses one instance per resource class, keep our own copy of it.
            $this->object = is_object($object) ? clone $object : $object;
        } elseif (method_exists($this->object, 'refresh')) {
            $this->object->refresh();
        }

        return $this;
    }

    /**
     * Merge the attributes of an API response into the loaded object.
     *
     * @param  array<string, mixed>  $values
     * @return $this
     */
    protected function fill(array $values)
    {
        if (is_object($this->object) && method_exists($this->object, 'refresh')) {
            $this->object->refresh($values);
        } else {
            $this->object = array_merge(is_array($this->object) ? $this->object : [], $values);
        }

        return $this;
    }

    /**
     * The result of a failed API call: an Error, or an exception when `omise.throw` is enabled.
     *
     * @param  array<string, mixed>  $error
     * @return Error
     *
     * @throws OmiseRequestException
     */
    protected function fail(array $error)
    {
        $error = new Error($error);

        event(new RequestFailed($error));

        if (config('omise.throw')) {
            $error->throw();
        }

        return $error;
    }

    /**
     * Whether the API call failed, see Error.
     */
    public function isError(): bool
    {
        return false;
    }

    /**
     * Throw an exception if the API call failed, otherwise return the object.
     *
     * @return $this
     *
     * @throws OmiseRequestException
     */
    public function throw()
    {
        return $this;
    }

    /**
     * Check if the object is properly loaded
     */
    public function isLoaded(): bool
    {
        return $this->object !== null;
    }

    /**
     * Check if a property exists
     */
    public function hasProperty(string $key): bool
    {
        if (! $this->object) {
            return false;
        }

        // Check if it's an array, or an SDK object (they are accessed as arrays)
        if (is_array($this->object) || $this->object instanceof ArrayAccess) {
            return isset($this->object[$key]);
        }

        // Check if it's an object with the property
        if (is_object($this->object)) {
            return property_exists($this->object, $key) || isset($this->object->$key);
        }

        return false;
    }

    /**
     * Get property with default value
     */
    public function getProperty(string $key, $default = null)
    {
        if (! $this->object) {
            return $default;
        }

        // Handle array access (arrays and SDK objects)
        if (is_array($this->object) || $this->object instanceof ArrayAccess) {
            return $this->object[$key] ?? $default;
        }

        // Handle object access
        if (is_object($this->object)) {
            return $this->object->$key ?? $default;
        }

        return $default;
    }

    /**
     * The attributes of the loaded object, as returned by the API.
     *
     * @return array<string, mixed>
     */
    protected function attributes(): array
    {
        if (is_array($this->object)) {
            return $this->object;
        }

        if (! is_object($this->object)) {
            return [];
        }

        return method_exists($this->object, 'toArray') ? $this->object->toArray() : get_object_vars($this->object);
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return $this->attributes();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Validate required properties exist
     */
    public function validateProperties(array $requiredProperties): bool
    {
        if (! $this->isLoaded()) {
            return false;
        }

        foreach ($requiredProperties as $property) {
            if (! $this->hasProperty($property)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get array values from object by the key like $object->$key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->object[$key]) ? $this->object[$key] : null;
    }

    /**
     * Whether the loaded object has a value for the key, for isset($object->$key).
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->object[$key]);
    }

    /**
     * Call the method from object by the key like $object->$key().
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     *
     * @throws BadMethodCallException when the loaded object has no such attribute
     */
    public function __call($method, $args)
    {
        $key = Str::snake($method);

        if ($this->isLoaded() && ! array_key_exists($key, $this->attributes())) {
            throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
        }

        return $this->$key;
    }
}
