<?php

namespace InstaScrapper\Scrapper\Core\Resources;

class CommentResource extends Resource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    protected static ?string $wrap = null;

    protected function array(): array
    {
        return [
            'id' => $this->node['id'] ?? $this->node['pk'],
            'text' => $this->node['text'],
            'likes' => $this->node['edge_liked_by']['count'] ?? $this->node['comment_like_count'],
            'replies' => $this->node['edge_threaded_comments']['count'] ?? $this->node['child_comment_count'],
            'user' => !is_null($user = $this->node['owner'] ??
                $this->node['user']) ? (new UserResource($user))->toArray() : null,
        ];
    }
}
