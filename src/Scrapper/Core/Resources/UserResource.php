<?php

namespace InstaScrapper\Scrapper\Core\Resources;

class UserResource extends Resource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    protected static ?string $wrap = 'data';

    protected function array(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'profile_picture' => $this->profile_pic_url,
        ];
    }
}
