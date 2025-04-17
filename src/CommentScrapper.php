<?php

namespace InstaScrapper;

use InstaScrapper\Exception\ScrapperErrorException;
use InstaScrapper\Scrapper\Core\Resources\CollectionBag;
use InstaScrapper\Scrapper\Core\Resources\CommentResource;

class CommentScrapper extends Scrapper
{
    protected const MAX_COMMENTS = 200;
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

    protected array $headers = [
        'X-FB-Friendly-Name' => 'PolarisPostCommentsPaginationQuery',
        'X-Root-Field-Name' => 'xdt_api__v1__media__media_id__comments__connection'
    ];

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
                'include_comment' => true
            ])->scrape();
            $maxComment = $this->with['max_comment'] ?? self::MAX_COMMENTS;
            $comments = $post['comments'];
            $pageInfo = $comments['page_info'];
            $collectionBag = new CollectionBag();
            $collectionBag->wrapper('data');
            $collectionBag->push($comments);
            $this->headers['Referer'] = 'https://www.instagram.com/p/' . $this->with['shortcode'] . '/';
            $this->payload = [
                "variables" => json_encode([
                    "after" => $pageInfo['end_cursor'] ?? null,
                    "before" => null,
                    "first" => 10,
                    "last" => null,
                    "media_id" => $post['id'],
                    "sort_order" => 'popular',
                    "__relay_internal__pv__PolarisIsLoggedInrelayprovider" => true
                ]),
                "server_timestamps" => true,
                'doc_id' => '9362508960506999'
            ];
            $this->send();
            if (!isset($this->response['data'])) {
                throw new ScrapperErrorException(
                    'Post comments not found',
                    $this->statusCode,
                    $this->statusCode,
                    $this->response
                );
            }
            CommentResource::wrap('comments');
            $comments = $this->response['data']['xdt_api__v1__media__media_id__comments__connection'];
            $pageInfo = $comments['page_info'];
            $collectionBag->wrapper('comments');
            $collectionBag->push(CommentResource::collect($comments['edges'] ?? []));
            if ($pageInfo['has_next_page'] && ($collectionBag->count() < $maxComment)) {
                while ($pageInfo['has_next_page'] && ($collectionBag->count() < $maxComment)) {
                    $this->payload['variables'] = $this->updateVariable(
                        $this->payload['variables'],
                        [
                            'after' => $pageInfo['end_cursor']
                        ]
                    );
                    sleep(self::SLEEPTIME);
                    $this->send();
                    if (!isset($this->response['data'])) {
                        throw new ScrapperErrorException(
                            'Post comments not found',
                            $this->statusCode,
                            $this->statusCode,
                            $this->response
                        );
                    }
                    $comments = $this->response['data']['xdt_api__v1__media__media_id__comments__connection'];
                    $pageInfo = $comments['page_info'];
                    $collectionBag->wrapper('comments');
                    $collectionBag->push(CommentResource::collect($comments['edges'] ?? []));
                }
            }
            return $collectionBag->all();
        } catch (\Exception $e) {
            throw $e;
        }
    }
    protected function updateVariable(string $variable, array $payload): string
    {
        $variable = json_decode($variable, true);
        return json_encode(array_merge($variable, $payload));
    }
}
