<?php

namespace InstaScrapper;

use InstaScrapper\Exception\ScrapperErrorException;
use InstaScrapper\Scrapper\Core\Resources\UserResource;

class LikeScrapper extends Scrapper
{
    /**
     * the endpoint to use for the request
     * @var string
     */
    protected ?string $endpoint = 'https://www.instagram.com/api/v1/media/:media_id/likers/';
    /**
     * the method to use for the request
     * @var string
     */
    protected ?string $method = 'GET';

    public function scrape(): mixed
    {
        if (!isset($this->with['shortcode'])) {
            throw new \Exception(
                'shortcode is required, include using the with()
                 method i.e ->with(["shortcode" => "post short code"])'
            );
        }
        try {
            $postScrapper = new PostScrapper();
            $post = $postScrapper->with([
                'shortcode' => $this->with['shortcode'],
                'include_comment' => false
            ])->scrape();
            $this->variables = [
                'media_id' => $post['id'],
            ];
            $this->send();
            if (!isset($this->response['users'])) {
                throw new ScrapperErrorException(
                    'Post likes not found',
                    $this->statusCode,
                    $this->statusCode,
                    $this->response
                );
            }
            UserResource::wrap('users');
            return UserResource::collect($this->response['users']);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
