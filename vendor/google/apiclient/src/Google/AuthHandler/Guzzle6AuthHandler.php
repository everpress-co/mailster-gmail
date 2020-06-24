<?php

namespace Mailster;

use Mailster\Google\Auth\CredentialsLoader;
use Mailster\Google\Auth\HttpHandler\HttpHandlerFactory;
use Mailster\Google\Auth\FetchAuthTokenCache;
use Mailster\Google\Auth\Middleware\AuthTokenMiddleware;
use Mailster\Google\Auth\Middleware\ScopedAccessTokenMiddleware;
use Mailster\Google\Auth\Middleware\SimpleMiddleware;
use Mailster\GuzzleHttp\Client;
use Mailster\GuzzleHttp\ClientInterface;
use Mailster\Psr\Cache\CacheItemPoolInterface;
/**
*
*/
class Google_AuthHandler_Guzzle6AuthHandler
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
        $middleware = new \Mailster\Google\Auth\Middleware\AuthTokenMiddleware($credentials, $authHttpHandler, $tokenCallback);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'google_auth';
        $http = new \Mailster\GuzzleHttp\Client($config);
        return $http;
    }
    public function attachToken(\Mailster\GuzzleHttp\ClientInterface $http, array $token, array $scopes)
    {
        $tokenFunc = function ($scopes) use($token) {
            return $token['access_token'];
        };
        $middleware = new \Mailster\Google\Auth\Middleware\ScopedAccessTokenMiddleware($tokenFunc, $scopes, $this->cacheConfig, $this->cache);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'scoped';
        $http = new \Mailster\GuzzleHttp\Client($config);
        return $http;
    }
    public function attachKey(\Mailster\GuzzleHttp\ClientInterface $http, $key)
    {
        $middleware = new \Mailster\Google\Auth\Middleware\SimpleMiddleware(['key' => $key]);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'simple';
        $http = new \Mailster\GuzzleHttp\Client($config);
        return $http;
    }
    private function createAuthHttp(\Mailster\GuzzleHttp\ClientInterface $http)
    {
        return new \Mailster\GuzzleHttp\Client(['base_uri' => $http->getConfig('base_uri'), 'exceptions' => \true, 'verify' => $http->getConfig('verify'), 'proxy' => $http->getConfig('proxy')]);
    }
}
/**
*
*/
\class_alias('Mailster\\Google_AuthHandler_Guzzle6AuthHandler', 'Google_AuthHandler_Guzzle6AuthHandler', \false);
