<?php

namespace InstaScrapper\Scrapper\Core\Resources;

class PostResource extends Resource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    protected static ?string $wrap = 'posts';

    protected function array(): array
    {
        return [
            'id' => $this->id,
            'shortcode' => $this->shortcode,
            'type' => $this->__typename,
            'thumbnail' => $this->thumbnail_src,
            'dimensions' =>  $this->dimensions,
            'title' => $this->title,
            'caption' => $this->edge_media_to_caption['edges'][0]['node']['text'] ?? null,
            'user' => (new UserResource($this->owner))->toArray(),
            'comments' => [
                'count' => $this->edge_media_to_parent_comment['count'],
                'page_info' => $this->edge_media_to_parent_comment['page_info'],
                'data' => CommentResource::collect($this->edge_media_to_parent_comment['edges'] ?? [])
            ],
        ];
    }
}
