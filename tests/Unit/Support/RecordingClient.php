<?php

declare(strict_types=1);

namespace Mailrify\Tests\Unit\Support;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RecordingClient implements ClientInterface
{
    /** @var array<string, mixed> */
    public array $lastOptions = [];

    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        throw new \BadMethodCallException('Not used in tests.');
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        throw new \BadMethodCallException('Not used in tests.');
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @param array<string, mixed> $options
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        $this->lastOptions = $options;

        return $this->response;
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @param array<string, mixed> $options
     */
    public function requestAsync(string $method, $uri = '', array $options = []): PromiseInterface
    {
        return Create::promiseFor($this->request($method, $uri, $options));
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @param array<string, mixed> $options
     */
    public function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @param array<string, mixed> $options
     */
    public function head(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('HEAD', $uri, $options);
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @param array<string, mixed> $options
     */
    public function put(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('PUT', $uri, $options);
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @param array<string, mixed> $options
     */
    public function post(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @param array<string, mixed> $options
     */
    public function patch(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $uri, $options);
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @param array<string, mixed> $options
     */
    public function delete(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $options);
    }

    /**
     * @param string|\Psr\Http\Message\UriInterface $uri
     * @param array<string, mixed> $options
     */
    public function options(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('OPTIONS', $uri, $options);
    }

    public function getConfig(?string $option = null): mixed
    {
        return null;
    }
}
