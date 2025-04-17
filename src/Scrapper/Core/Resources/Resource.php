<?php

namespace InstaScrapper\Scrapper\Core\Resources;

use ArrayAccess;
use InstaScrapper\Scrapper\Contracts\Resources;

abstract class Resource implements Resources, ArrayAccess
{
    protected static ?string $wrap = null;

    protected array $resource;


    public function __construct(array|object $resource)
    {
        $this->resource = is_object($resource) ? (array) $resource : $resource;
    }

    public function toArray(): array
    {
        return $this->array();
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    abstract protected function array(): array;

    public static function wrap(string $wrapper): void
    {
        self::$wrap = $wrapper;
    }

    public static function collect(array $collection): array
    {
        $data = [];

        foreach ($collection as $item) {
            $instance = new static($item);
            $data[] = $instance->toArray();
        }
        return (is_null(self::$wrap) || empty(self::$wrap)) ? $data : [self::$wrap => $data];
    }

    public function __get($name)
    {
        return isset($this->resource[$name]) ? $this->resource[$name] : null;
    }

    public function __set($name, $value)
    {
        $this->resource[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->resource[$name]);
    }

    public function __unset($name)
    {
        unset($this->resource[$name]);
    }
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->resource[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $data = $this->resource[$offset] ?? null;

        if (is_array($data) || is_object($data)) {
            return new static($data);
        }

        return $data;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->resource[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->resource[$offset]);
    }
}
