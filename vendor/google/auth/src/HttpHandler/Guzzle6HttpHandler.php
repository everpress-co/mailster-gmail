<?php

namespace Mailster\Google\Auth\HttpHandler;

use Mailster\GuzzleHttp\ClientInterface;
use Mailster\Psr\Http\Message\RequestInterface;
use Mailster\Psr\Http\Message\ResponseInterface;
class Guzzle6HttpHandler
{
    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @param ClientInterface $client
     */
    public function __construct(\Mailster\GuzzleHttp\ClientInterface $client)
    {
        $this->client = $client;
    }
    /**
     * Accepts a PSR-7 request and an array of options and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     * @param array $options
     * @return ResponseInterface
     */
    public function __invoke(\Mailster\Psr\Http\Message\RequestInterface $request, array $options = [])
    {
        return $this->client->send($request, $options);
    }
    /**
     * Accepts a PSR-7 request and an array of options and returns a PromiseInterface
     *
     * @param RequestInterface $request
     * @param array $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function async(\Mailster\Psr\Http\Message\RequestInterface $request, array $options = [])
    {
        return $this->client->sendAsync($request, $options);
    }
}
