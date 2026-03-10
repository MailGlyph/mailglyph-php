<?php

declare(strict_types=1);

namespace MailGlyph\Tests\Unit\Support;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class SequenceClient implements ClientInterface
{
    /** @var list<ResponseInterface> */
    private array $responses;

    /**
     * @param list<ResponseInterface> $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        throw new \BadMethodCallException('Not used in tests.');
    }

    /**
     * @param array<string, mixed> $options
     */
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
        $response = array_shift($this->responses);

        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException('No responses left in SequenceClient');
        }

        return $response;
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
     * @param array<string, mixed> $options
     */
    public function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function head(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('HEAD', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function put(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('PUT', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function patch(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function delete(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $options);
    }

    /**
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
