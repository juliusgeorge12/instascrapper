<?php

namespace InstaScrapper\Scrapper\Contracts;

interface Resources
{
    /**
     * Convert the resource into an array.
     * @return array<string, mixed>
     */
    public function toArray(): array;
    /**
     * Convert the resource into a JSON string.
     * @return string
     * @throws \JsonException
     * @throws \InvalidArgumentException
     * @throws \TypeError
     * @throws \ErrorException
     * @throws \Exception
     */
    public function toJson(): string;
}
