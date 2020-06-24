<?php

namespace Mailster;

use Mailster\Google\Auth\CredentialsLoader;
use Mailster\Google\Auth\HttpHandler\HttpHandlerFactory;
use Mailster\Google\Auth\FetchAuthTokenCache;
use Mailster\Google\Auth\Subscriber\AuthTokenSubscriber;
use Mailster\Google\Auth\Subscriber\ScopedAccessTokenSubscriber;
use Mailster\Google\Auth\Subscriber\SimpleSubscriber;
use Mailster\GuzzleHttp\Client;
use Mailster\GuzzleHttp\ClientInterface;
use Mailster\Psr\Cache\CacheItemPoolInterface;
/**
*
*/
class Google_AuthHandler_Guzzle5AuthHandler
{
    protected $cache;
    protected $cacheConfig;
    public function __construct(\Mailster\Psr\Cache\CacheItemPoolInterface $cache = null, array $cacheConfig = [])
    {
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
    }
    public function attachCredentials(\Mailster\GuzzleHttp\ClientInterface $http, \Mailster\Google\Auth\CredentialsLoader $credentials, callable $tokenCallback = null)
    {
        // use the provided cache
        if ($this->cache) {
            $credentials = new \Mailster\Google\Auth\FetchAuthTokenCache($credentials, $this->cacheConfig, $this->cache);
        }
        // if we end up needing to make an HTTP request to retrieve credentials, we
        // can use our existing one, but we need to throw exceptions so the error
        // bubbles up.
        $authHttp = $this->createAuthHttp($http);
        $authHttpHandler = \Mailster\Google\Auth\HttpHandler\HttpHandlerFactory::build($authHttp);
        $subscriber = new \Mailster\Google\Auth\Subscriber\AuthTokenSubscriber($credentials, $authHttpHandler, $tokenCallback);
        $http->setDefaultOption('auth', 'google_auth');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
    public function attachToken(\Mailster\GuzzleHttp\ClientInterface $http, array $token, array $scopes)
    {
        $tokenFunc = function ($scopes) use($token) {
            return $token['access_token'];
        };
        $subscriber = new \Mailster\Google\Auth\Subscriber\ScopedAccessTokenSubscriber($tokenFunc, $scopes, $this->cacheConfig, $this->cache);
        $http->setDefaultOption('auth', 'scoped');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
    public function attachKey(\Mailster\GuzzleHttp\ClientInterface $http, $key)
    {
        $subscriber = new \Mailster\Google\Auth\Subscriber\SimpleSubscriber(['key' => $key]);
        $http->setDefaultOption('auth', 'simple');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
    private function createAuthHttp(\Mailster\GuzzleHttp\ClientInterface $http)
    {
        return new \Mailster\GuzzleHttp\Client(['base_url' => $http->getBaseUrl(), 'defaults' => ['exceptions' => \true, 'verify' => $http->getDefaultOption('verify'), 'proxy' => $http->getDefaultOption('proxy')]]);
    }
}
/**
*
*/
\class_alias('Mailster\\Google_AuthHandler_Guzzle5AuthHandler', 'Google_AuthHandler_Guzzle5AuthHandler', \false);
