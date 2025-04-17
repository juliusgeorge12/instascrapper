<?php

namespace InstaScrapper\Scrapper\Core\Resources;

use InstaScrapper\Scrapper\Contracts\Resources;

class CollectionBag implements \Countable, Resources
{
    /**
     * The collection of items
     * @var array
     */
    protected array $items = [];
    /**
     * the wrapper used to wrap the items
     * @var string
     */
    protected ?string $wrapper = null;

    /**
     * unwind the wrapper
     * @return void
     */
    protected function unwindWrapper(): void
    {
        $this->wrapper = null;
    }
    /**
     * set the wrapper
     * @param string $wrapper
     * @return void
     */
    public function wrapper(string $wrapper): self
    {
        $this->wrapper = $wrapper;
        return $this;
    }
    /**
     * add an item to the collection
     * @param array $items
     * @return CollectionBag
     */
    public function push(array $items): void
    {
        if ($this->wrapper) {
            $items = $items[$this->wrapper] ?? $items;
        }
        foreach ($items as $item) {
            $this->items[] = $item;
        }
        $this->unwindWrapper();
    }
    /**
     * get the items in the collection
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }
    /**
     * get the first item in the collection
     * @return array|null
     */
    public function first(): ?array
    {
        return $this->items[0] ?? null;
    }
    /**
     * get the last item in the collection
     * @return array|null
     */
    public function last(): ?array
    {
        return $this->items[count($this->items) - 1] ?? null;
    }
    /**
     * get the count of the items in the collection
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
    /**
     * get the items in the collection as json
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->items, JSON_PRETTY_PRINT);
    }
    /**
     * get the items in the collection as array
     * @return array
     */
    public function toArray(): array
    {
        return $this->items;
    }
}
