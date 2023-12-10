<?php

namespace OpenLibrary\API;

use GuzzleHttp\Psr7\Response;

/**
 * A client to access the Open Library API
 */
class Client
{
    private $requestHandler;

    /**
     * Create a new Client
     */
    public function __construct()
    {
        $this->requestHandler = new RequestHandler();
    }

    /**
     * Retrieve RequestHandler instance
     *
     * @return RequestHandler
     */
    private function requestHandler()
    {
        return $this->requestHandler;
    }

    /**
     * Get a book by its Open Library ID
     *
     * @param string $olid the OLID
     * @return object the response
     */
    public function getBookByOLID($olid)
    {
        return $this->getRequest(
            sprintf('/books/%s.json', $olid)
        );
    }

    /**
     * Get a book by its ISBN
     *
     * @param string $isbn the ISBN
     * @return object the response
     */
    public function getBookByISBN($isbn)
    {
        $searchResults = $this->searchByISBN($isbn);
        $searchPath = 'ISBN:' . $isbn;
        if (!\property_exists($searchResults, $searchPath)) {
            throw new RequestException(new Response(404, [], 'No match for ISBN ' . $isbn));
        }
        $pathParts = \explode('/', \parse_url($searchResults->$searchPath->info_url, \PHP_URL_PATH));
        return $this->getBookByOLID($pathParts[2]);
    }

    public function searchByISBN($isbn)
    {
        switch (strlen($isbn)) {
            case 10:
                break;
            case 13:
                break;
            default:
                throw new \InvalidArgumentException('ISBN must be 10 or 13 characters.');
        }

        return $this->getRequest(
            '/api/books',
            [
                'query' =>
                [
                    'format' => 'json',
                    'bibkeys' => 'ISBN:' . $isbn
                ]
            ]
        );
    }

    public function searchByLCCN($isbn)
    {
        if (strlen($isbn) !== 12) {
            throw new \InvalidArgumentException('LCCN must be 10 or 13 characters.');
        }

        return $this->getRequest(
            '/api/books',
            [
                'query' =>
                [
                    'format' => 'json',
                    'bibkeys' => 'LCCN:' . $isbn
                ]
            ]
        );
    }

    /**
     * FIXME: Find editions by Library of Congress Control Number
     *
     * @param string $lccn the LCNN
     * @return object the response array
     */
    public function queryEditionsByLCCN($lccn)
    {
        return $this->getRequest(
            '/query.json',
            [
                'type' => '/type/edition',
                'lccn' => $lccn,
                '*' => ''
            ]
        );
    }

    /**
     * FIXME: Find editions by Online Computer Library Center number
     *
     * @param string $oclc the OCLC number
     * @return object the response array
     */
    public function queryEditionsByOCLC($oclc)
    {
        return $this->getRequest(
            '/query.json',
            [
                'type' => '/type/edition',
                'oclc_numbers' => $oclc,
                '*' => ''
            ]
        );
    }

    /**
     * Find an author in Open Library by their key
     *
     * @param string $key the Open Library author key
     * @return object the response array
     */
    public function getAuthorByKey($key)
    {
        return $this->getRequest(
            sprintf('/authors/%s.json', $key)
        );
    }

    /**
     * Get the editions of a work
     *
     * @param string $work the Open Library work key
     * @param int $limit number of results to limit by
     * @param int $offset number of results to offset by
     * @return object the response array
     */
    public function getEditionsOfWork($work, $limit = 20, $offset = 0)
    {
        return $this->getRequest(
            sprintf('/works/%s/editions.json', $work),
            [
                'limit' => (int)$limit,
                'offset' => (int)$offset,
                '*' => ''
            ]
        );
    }

    /**
     * Make a GET request to the given endpoint and return the response
     *
     * @param string $path the path to call on
     * @param array  $options the options to call with
     *
     * @return object the response object (parsed)
     */
    private function getRequest($path, $options = array())
    {
        $response = $this->makeRequest('GET', $path, $options);

        return $this->parseResponse($response);
    }

    /**
     * Parse a response and return an appropriate result
     *
     * @param \stdClass $response the response from the server
     *
     * @throws RequestException
     * @return object the response data
     */
    private function parseResponse($response)
    {
        $response->json = json_decode($response->body);

        if ($response->status < 400) {
            return $response->json;
        } else {
            throw new RequestException($response);
        }
    }

    /**
     * Make a request to the given endpoint and return the response
     *
     * @param string $method the method to call: GET, POST
     * @param string $path the path to call on
     * @param array  $options the options to call with
     *
     * @return \stdClass the response object (not parsed)
     */
    private function makeRequest($method, $path, $options)
    {
        return $this->requestHandler()->request($method, $path, $options);
    }
}
