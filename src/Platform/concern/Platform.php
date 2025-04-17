<?php

namespace InstaScrapper\Platform\Concern;

interface Platform
{
    /**
     * get the generated platform headers
     *
     * @return array
     */
    public function getPlatFormHeaders(): array;

    /**
     * set the platform headers
     *
     * @return void
     */
    public function setHeaders(): void;
}
