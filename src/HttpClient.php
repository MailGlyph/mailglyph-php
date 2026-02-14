<?php

declare(strict_types=1);

namespace Mailrify;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Mailrify\Exceptions\ApiException;
use Mailrify\Exceptions\AuthenticationException;
use Mailrify\Exceptions\MailrifyException;
use Mailrify\Exceptions\NotFoundException;
use Mailrify\Exceptions\RateLimitException;
use Mailrify\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;

final class HttpClient
{
    private const DEFAULT_BASE_URL = 'https://api.mailrify.com';

    private const DEFAULT_TIMEOUT_MS = 30000;

    private const DEFAULT_MAX_RETRIES = 3;

    private readonly ClientInterface $client;

    private readonly string $apiKey;

    private readonly string $keyType;

    private readonly string $userAgent;

    private readonly int $maxRetries;

    private readonly float $timeoutSeconds;

    /** @var callable(int): void */
    private $sleep;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(string $apiKey, array $config = [])
    {
        $this->apiKey = $apiKey;
        $this->keyType = $this->detectKeyType($apiKey);
        $version = isset($config['version']) ? (string) $config['version'] : '0.2.0';
        $this->userAgent = isset($config['userAgent'])
            ? (string) $config['userAgent']
            : sprintf('mailrify-php/%s', $version);

        $this->maxRetries = isset($config['maxRetries'])
            ? max(0, (int) $config['maxRetries'])
            : self::DEFAULT_MAX_RETRIES;
        $timeoutMs = isset($config['timeout']) ? (int) $config['timeout'] : self::DEFAULT_TIMEOUT_MS;
        $this->timeoutSeconds = $timeoutMs / 1000;

        $this->sleep = isset($config['sleep']) && is_callable($config['sleep'])
            ? $config['sleep']
            : static function (int $milliseconds): void {
                usleep($milliseconds * 1000);
            };

        if (isset($config['client']) && $config['client'] instanceof ClientInterface) {
            $this->client = $config['client'];

            return;
        }

        $baseUrl = isset($config['baseUrl']) ? rtrim((string) $config['baseUrl'], '/') : self::DEFAULT_BASE_URL;
        $handler = $config['handler'] ?? null;

        $clientConfig = [
            'base_uri' => $baseUrl,
            'timeout' => $this->timeoutSeconds,
        ];

        if ($handler !== null) {
            $clientConfig['handler'] = $handler;
        }

        $this->client = new Client($clientConfig);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     *
     * @throws MailrifyException
     */
    public function request(string $method, string $path, array $options = []): array
    {
        $this->assertKeyAllowed($path);

        $attempt = 0;

        while (true) {
            try {
                $response = $this->client->request($method, $path, $this->buildOptions($options));
                $statusCode = $response->getStatusCode();
                $responseData = $this->decodeResponse($response);

                if ($statusCode >= 200 && $statusCode < 300) {
                    return $responseData;
                }

                $exception = $this->mapException($statusCode, $responseData);

                if ($this->shouldRetry($statusCode) && $attempt < $this->maxRetries) {
                    $this->sleepBeforeRetry($attempt, $response);
                    $attempt++;

                    continue;
                }

                throw $exception;
            } catch (RequestException $exception) {
                $statusCode = $exception->hasResponse() ? $exception->getResponse()?->getStatusCode() : null;

                if ($statusCode !== null && $exception->hasResponse()) {
                    $data = $this->decodeResponse($exception->getResponse());
                    $mapped = $this->mapException($statusCode, $data);

                    if ($this->shouldRetry($statusCode) && $attempt < $this->maxRetries) {
                        $this->sleepBeforeRetry($attempt, $exception->getResponse());
                        $attempt++;

                        continue;
                    }

                    throw $mapped;
                }

                if ($attempt < $this->maxRetries) {
                    $this->sleepBeforeRetry($attempt, null);
                    $attempt++;

                    continue;
                }

                throw new ApiException('Network request failed', null, [], $exception);
            } catch (GuzzleException $exception) {
                if ($attempt < $this->maxRetries) {
                    $this->sleepBeforeRetry($attempt, null);
                    $attempt++;

                    continue;
                }

                throw new ApiException('HTTP client error', null, [], $exception);
            }
        }
    }

    private function assertKeyAllowed(string $path): void
    {
        if ($this->keyType === 'invalid') {
            throw new AuthenticationException('Invalid API key format. Expected key to start with sk_ or pk_.');
        }

        if ($path === '/v1/track' && $this->keyType !== 'pk') {
            throw new AuthenticationException('The /v1/track endpoint requires a public key (pk_*).');
        }

        if ($path !== '/v1/track' && $this->keyType !== 'sk') {
            throw new AuthenticationException('This endpoint requires a secret key (sk_*).');
        }
    }

    private function detectKeyType(string $apiKey): string
    {
        if (str_starts_with($apiKey, 'sk_')) {
            return 'sk';
        }

        if (str_starts_with($apiKey, 'pk_')) {
            return 'pk';
        }

        return 'invalid';
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function buildOptions(array $options): array
    {
        $headers = [
            'Authorization' => sprintf('Bearer %s', $this->apiKey),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->userAgent,
        ];

        if (isset($options['headers']) && is_array($options['headers'])) {
            /** @var array<string, mixed> $customHeaders */
            $customHeaders = $options['headers'];
            $headers = array_merge($headers, $customHeaders);
        }

        $options['headers'] = $headers;
        $options['http_errors'] = false;
        $options['timeout'] = $this->timeoutSeconds;

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(?ResponseInterface $response): array
    {
        if ($response === null) {
            return [];
        }

        $rawBody = (string) $response->getBody();

        if ($rawBody === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($rawBody, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $responseData
     */
    private function mapException(int $statusCode, array $responseData): MailrifyException
    {
        $message = $this->extractErrorMessage($responseData);

        return match ($statusCode) {
            400 => new ValidationException($message, $statusCode, $responseData),
            401 => new AuthenticationException($message, $statusCode, $responseData),
            404 => new NotFoundException($message, $statusCode, $responseData),
            429 => new RateLimitException($message, $statusCode, $responseData),
            default => new ApiException($message, $statusCode, $responseData),
        };
    }

    /**
     * @param array<string, mixed> $responseData
     */
    private function extractErrorMessage(array $responseData): string
    {
        if (isset($responseData['message']) && is_string($responseData['message'])) {
            return $responseData['message'];
        }

        if (isset($responseData['error']) && is_string($responseData['error'])) {
            return $responseData['error'];
        }

        if (isset($responseData['error']) && is_array($responseData['error'])) {
            $nestedMessage = $responseData['error']['message'] ?? null;
            if (is_string($nestedMessage) && $nestedMessage !== '') {
                return $nestedMessage;
            }
        }

        return 'Mailrify API request failed';
    }

    private function shouldRetry(int $statusCode): bool
    {
        return $statusCode === 429 || $statusCode >= 500;
    }

    private function sleepBeforeRetry(int $attempt, ?ResponseInterface $response): void
    {
        $retryAfterMs = $this->parseRetryAfter($response);
        $baseDelayMs = (int) (1000 * (2 ** $attempt));
        $jitterMs = random_int(0, 250);
        $delayMs = $retryAfterMs ?? ($baseDelayMs + $jitterMs);

        ($this->sleep)($delayMs);
    }

    private function parseRetryAfter(?ResponseInterface $response): ?int
    {
        if ($response === null || !$response->hasHeader('Retry-After')) {
            return null;
        }

        $value = trim($response->getHeaderLine('Retry-After'));

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return max(0, (int) $value * 1000);
        }

        $date = DateTimeImmutable::createFromFormat(DATE_RFC7231, $value);

        if ($date === false) {
            $timestamp = strtotime($value);

            if ($timestamp === false) {
                return null;
            }

            return max(0, ($timestamp - time()) * 1000);
        }

        return max(0, ((int) $date->format('U') - time()) * 1000);
    }
}
