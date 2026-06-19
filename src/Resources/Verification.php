<?php

declare(strict_types=1);

namespace MailGlyph\Resources;

use GuzzleHttp\Psr7\LazyOpenStream;
use MailGlyph\HttpClient;
use MailGlyph\Models\BulkEmailValidationDownload;
use MailGlyph\Models\BulkEmailValidationJob;
use MailGlyph\Models\VerificationCreditLedgerEntry;
use MailGlyph\Models\VerificationCreditSummary;
use MailGlyph\Models\VerifyEmailResult;

final class Verification
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    public function validate(string $email): VerifyEmailResult
    {
        $response = $this->httpClient->request('POST', '/v1/verify', [
            'json' => ['email' => $email],
        ]);

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return VerifyEmailResult::fromArray($data);
    }

    public function createBulk(string $filePath): BulkEmailValidationJob
    {
        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException(sprintf('Unable to open verification file: %s', $filePath));
        }

        $response = $this->httpClient->request('POST', '/v1/verify/files', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => new LazyOpenStream($filePath, 'rb'),
                    'filename' => basename($filePath),
                ],
            ],
        ]);

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return BulkEmailValidationJob::fromArray($data);
    }

    /**
     * @param array{limit?: int, cursor?: string, search?: string, status?: string} $params
     *
     * @return array{items: list<BulkEmailValidationJob>, nextCursor: ?string}
     */
    public function listBulk(array $params = []): array
    {
        $response = $this->httpClient->request('GET', '/v1/verify/files', [
            'query' => $this->cleanParams($params),
        ]);

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $jobs = array_map(
            static fn(mixed $item): BulkEmailValidationJob => BulkEmailValidationJob::fromArray(
                is_array($item) ? $item : []
            ),
            $items
        );

        return [
            'items' => array_values($jobs),
            'nextCursor' => isset($data['nextCursor']) ? (string) $data['nextCursor'] : null,
        ];
    }

    public function getBulk(string $jobId): BulkEmailValidationJob
    {
        $response = $this->httpClient->request('GET', sprintf('/v1/verify/files/%s', rawurlencode($jobId)));
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return BulkEmailValidationJob::fromArray($data);
    }

    public function continueBulk(string $jobId): BulkEmailValidationJob
    {
        $response = $this->httpClient->request(
            'POST',
            sprintf('/v1/verify/files/%s/continue', rawurlencode($jobId))
        );
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return BulkEmailValidationJob::fromArray($data);
    }

    /**
     * @param array{filter?: string, format?: string} $params
     */
    public function downloadBulk(string $jobId, array $params = []): BulkEmailValidationDownload
    {
        $response = $this->httpClient->requestRaw(
            'GET',
            sprintf('/v1/verify/files/%s/download', rawurlencode($jobId)),
            ['query' => $this->cleanParams($params)]
        );

        return new BulkEmailValidationDownload(
            (string) $response->getBody(),
            $response->getHeaderLine('Content-Type'),
            $this->extractFilename($response->getHeaderLine('Content-Disposition'))
        );
    }

    public function deleteBulk(string $jobId): int
    {
        $response = $this->httpClient->request('DELETE', sprintf('/v1/verify/files/%s', rawurlencode($jobId)));
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return (int) ($data['refundedCredits'] ?? 0);
    }

    public function credits(): VerificationCreditSummary
    {
        $response = $this->httpClient->request('GET', '/v1/verification-credits');
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return VerificationCreditSummary::fromArray($data);
    }

    /**
     * @param array{limit?: int, cursor?: string} $params
     *
     * @return array{items: list<VerificationCreditLedgerEntry>, nextCursor: ?string}
     */
    public function ledger(array $params = []): array
    {
        $response = $this->httpClient->request('GET', '/v1/verification-credits/ledger', [
            'query' => $this->cleanParams($params),
        ]);

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $entries = array_map(
            static fn(mixed $item): VerificationCreditLedgerEntry => VerificationCreditLedgerEntry::fromArray(
                is_array($item) ? $item : []
            ),
            $items
        );

        return [
            'items' => array_values($entries),
            'nextCursor' => isset($data['nextCursor']) ? (string) $data['nextCursor'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function cleanParams(array $params): array
    {
        return array_filter(
            $params,
            static fn(mixed $value): bool => $value !== null
        );
    }

    private function extractFilename(string $contentDisposition): ?string
    {
        if ($contentDisposition === '') {
            return null;
        }

        if (preg_match('/filename\*=UTF-8\'\'([^;]+)/', $contentDisposition, $matches) === 1) {
            return rawurldecode(trim($matches[1], '"'));
        }

        if (preg_match('/filename="([^"]+)"/', $contentDisposition, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/filename=([^;]+)/', $contentDisposition, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }
}
