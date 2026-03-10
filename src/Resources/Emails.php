<?php

declare(strict_types=1);

namespace MailGlyph\Resources;

use MailGlyph\HttpClient;
use MailGlyph\Models\SendEmailResult;
use MailGlyph\Models\VerifyEmailResult;

final class Emails
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param array{
     *   to: string|array<string, mixed>|array<int, string|array<string, mixed>>,
     *   from?: string|array<string, mixed>,
     *   subject?: string,
     *   body?: string,
     *   text?: string,
     *   template?: string,
     *   data?: array<string, mixed>,
     *   headers?: array<string, string>,
     *   reply?: string,
     *   attachments?: array<int, array{filename: string, content: string, contentType: string}>,
     *   subscribed?: bool,
     *   name?: string
     * } $params
     *
     * The plain text version of the message.
     * If not provided, the `body` will be used to generate a plain text
     * version. You can opt out of this behavior by setting value to an empty
     * string.
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
