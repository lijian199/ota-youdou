<?php

namespace Cncn\Youdou\Common;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class HttpClient
{

    private $httpClient = null;

    /**
     * HttpClient constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        if ($this->httpClient ){
            return $this->httpClient;
        }
        $maxRetries = $config['max_retries'] ?? 3;
        $retryInterval = $config['retry_interval'] ?? 1000;
        $timeout = $config['timeout'] ?? 10;
        $connectTimeout = $config['connect_timeout'] ?? 3;
        $handlerStack = HandlerStack::create();
        $handlerStack->push(Middleware::retry(function(
            $retries,
            \GuzzleHttp\Psr7\Request $request,
            Response $response = null,
            \Exception $exception = null
        ) use ($maxRetries) {

            if ($retries > $maxRetries) {
                return false;
            }
            // 只有在没有响应或者 500 错误的时候才去重试
            if ($exception instanceof ConnectException
                || ($response && $response->getStatusCode() >= 500)
            ) {
                return true;
            }
            return false;
        }, function() use ($retryInterval) {
            return $retryInterval; // 重试间隔 TODO
        }));
        $this->httpClient = new Client(
            [
                'timeout' => $timeout,
                'connect_timeout' => $connectTimeout,
                'handler' => $handlerStack,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]
        );
    }

    /**
     * @param $url
     * @param $body
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post($url, $body)
    {
        return $this->httpClient->request('post', $url, $body);
    }
}