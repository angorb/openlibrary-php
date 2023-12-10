<?php

namespace OpenLibrary\API;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;

/**
 * A request handler for Open Library API requests
 */
class RequestHandler
{
    private string $base_url;
    private string $version;
    private GuzzleClient $client;

    /**
     * Instantiate a new RequestHandler
     */
    public function __construct()
    {
        $this->base_url = 'https://openlibrary.org';

        $this->version = '2.0.0';

        $this->client = new GuzzleClient([
            'base_uri' => $this->base_url,
            'allow_redirects' => false,
            'headers' => [
                'User-Agent' => 'openlibrary-php/' . $this->version
            ]
        ]);
    }

    /**
     * Set the base url for this request handler
     * // TODO now does nothing relevant
     *
     * @param string $url The base url
     */
    public function setBaseUrl($url)
    {
        $this->base_url = $url;
    }

    /**
     * Make a request with this request handler
     *
     * @param string $method one of GET, POST
     * @param string $path the path to hit
     * @param array $options the array of params
     *
     * @return \stdClass response object
     */
    public function request($method, $path, $options)
    {
        // Ensure there are options
        $options = $options ?: [];

        // Collapse Guzzle's errors to deal with at the Client level
        try {
            $response = $this->client->get($path, $options);
        } catch (\Throwable $t) {
            // TODO rough fail, this needs work
            $response = new Response('500', [], 'No Response');
        }

        // Construct the object that the Client expects to see, and return it
        $obj = new \stdClass();
        $obj->status = $response->getStatusCode();
        $obj->body = $response->getBody();
        $obj->headers = $response->getHeaders();

        return $obj;
    }
}
