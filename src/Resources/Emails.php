<?php

declare(strict_types=1);

namespace Mailrify\Resources;

use Mailrify\HttpClient;
use Mailrify\Models\SendEmailResult;
use Mailrify\Models\VerifyEmailResult;

final class Emails
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function send(array $params): SendEmailResult
    {
        $response = $this->httpClient->request('POST', '/v1/send', [
            'json' => $params,
        ]);

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return SendEmailResult::fromArray($data);
    }

    public function verify(string $email): VerifyEmailResult
    {
        $response = $this->httpClient->request('POST', '/v1/verify', [
            'json' => ['email' => $email],
        ]);

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return VerifyEmailResult::fromArray($data);
    }
}
