<?php

namespace InstaScrapper;

use InstaScrapper\Exception\ScrapperErrorException;
use InstaScrapper\Scrapper\Core\Resources\PostResource;

class PostScrapper extends Scrapper
{
    /**
     * the endpoint to use for the request
     * @var string
     */
    protected ?string $endpoint = 'https://www.instagram.com/graphql/query';
    /**
     * the method to use for the request
     * @var string
     */
    protected ?string $method = 'POST';

    public function scrape(): mixed
    {
        if (!isset($this->with['shortcode'])) {
            throw new \Exception(
                'shortcode is required, include using the with() 
                 method i.e ->with(["shortcode" => "post short code"])'
            );
        }
        $this->headers['Referer'] = 'https://www.instagram.com/p/' . $this->with['shortcode'] . '/';
        $this->payload = [
            'doc_id' => '8845758582119845',
            'variables' => json_encode([
                'shortcode' => $this->with['shortcode'],
                'fetch_tagged_user_count' => null,
                'hoisted_comment_id' => null,
                'hoisted_reply_id' => null
            ])
        ];
        $this->send();
        if (!isset($this->response['data'])) {
            throw new ScrapperErrorException(
                'Post data not found',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        $includeComment = isset($this->with['include_comment']) ? $this->with['include_comment'] : false;
        $result = (new PostResource($this->response['data']['xdt_shortcode_media']))->toArray();
        if (!$includeComment) {
            unset($result['comments']);
        }
        return $result;
    }
}
