<?php

declare(strict_types=1);

namespace MailGlyph\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use MailGlyph\Exceptions\NotFoundException;
use MailGlyph\Resources\Verification;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class VerificationTest extends TestCase
{
    use CreatesHttpClient;

    public function testValidateEmailReturnsEnhancedResult(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'email' => 'user@gmail.com',
                    'valid' => true,
                    'validationMethod' => 'smtp',
                    'smtpStatus' => 'Valid',
                    'smtpDiagnosis' => 'Mailbox exists and can receive mail.',
                    'creditsConsumed' => 1,
                    'isDisposable' => false,
                    'isAlias' => false,
                    'isTypo' => false,
                    'isPlusAddressed' => false,
                    'isRandomInput' => false,
                    'isPersonalEmail' => true,
                    'isCatchAll' => false,
                    'isGreylisted' => false,
                    'domainExists' => true,
                    'hasWebsite' => true,
                    'hasMxRecords' => true,
                    'reasons' => [],
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $verification = new Verification($client);
        $result = $verification->validate('user@gmail.com');
        $payload = $this->getRequestJson($history);

        self::assertSame('/v1/verify', $history[0]['request']->getUri()->getPath());
        self::assertSame('user@gmail.com', $payload['email']);
        self::assertSame('smtp', $result->validationMethod);
        self::assertSame('Valid', $result->smtpStatus);
        self::assertSame(1, $result->creditsConsumed);
    }

    public function testCreateBulkEmailValidationUploadsMultipartFile(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(202, [], json_encode([
                'success' => true,
                'data' => $this->jobPayload(['id' => 'job_1', 'status' => 'QUEUED']),
            ], JSON_THROW_ON_ERROR)),
        ], $history);
        $filePath = $this->createEmailFile();

        try {
            $verification = new Verification($client);
            $job = $verification->createBulk($filePath);
        } finally {
            @unlink($filePath);
        }

        /** @var RequestInterface $request */
        $request = $history[0]['request'];
        self::assertSame('/v1/verify/files', $request->getUri()->getPath());
        self::assertStringStartsWith('multipart/form-data;', $request->getHeaderLine('Content-Type'));
        self::assertStringContainsString('valid@example.com', (string) $request->getBody());
        self::assertSame('job_1', $job->id);
        self::assertSame('QUEUED', $job->status);
    }

    public function testListBulkEmailValidations(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'items' => [$this->jobPayload(['id' => 'job_1', 'status' => 'COMPLETED'])],
                    'nextCursor' => 'cursor_2',
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $verification = new Verification($client);
        $result = $verification->listBulk(['limit' => 10, 'cursor' => 'cursor_1', 'status' => 'COMPLETED']);
        $query = $this->getRequestQuery($history);

        self::assertSame('10', $query['limit']);
        self::assertSame('cursor_1', $query['cursor']);
        self::assertSame('COMPLETED', $query['status']);
        self::assertSame('cursor_2', $result['nextCursor']);
        self::assertSame('job_1', $result['items'][0]->id);
    }

    public function testGetBulkEmailValidation(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => $this->jobPayload(['id' => '8a607588-1d7c-4d4f-9807-2a625fb20b14']),
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $verification = new Verification($client);
        $job = $verification->getBulk('8a607588-1d7c-4d4f-9807-2a625fb20b14');

        self::assertSame(
            '/v1/verify/files/8a607588-1d7c-4d4f-9807-2a625fb20b14',
            $history[0]['request']->getUri()->getPath()
        );
        self::assertSame('8a607588-1d7c-4d4f-9807-2a625fb20b14', $job->id);
    }

    public function testGetBulkEmailValidationMapsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(404, [], json_encode(['message' => 'Not found'], JSON_THROW_ON_ERROR)),
        ], $history);

        $verification = new Verification($client);
        $verification->getBulk('missing');
    }

    public function testContinueBulkEmailValidation(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => $this->jobPayload(['id' => 'job_1', 'status' => 'QUEUED']),
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $verification = new Verification($client);
        $job = $verification->continueBulk('job_1');

        self::assertSame('/v1/verify/files/job_1/continue', $history[0]['request']->getUri()->getPath());
        self::assertSame('QUEUED', $job->status);
    }

    public function testDownloadBulkEmailValidationResults(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="results.csv"',
            ], "email,status\nvalid@example.com,Valid\n"),
        ], $history);

        $verification = new Verification($client);
        $download = $verification->downloadBulk('job_1', ['filter' => 'valid', 'format' => 'csv']);
        $query = $this->getRequestQuery($history);

        self::assertSame('/v1/verify/files/job_1/download', $history[0]['request']->getUri()->getPath());
        self::assertSame('valid', $query['filter']);
        self::assertSame('csv', $query['format']);
        self::assertSame('text/csv', $download->contentType);
        self::assertSame('results.csv', $download->filename);
        self::assertStringContainsString('valid@example.com', $download->contents);
    }

    public function testDeleteBulkEmailValidationReturnsRefundedCredits(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => ['refundedCredits' => 2],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $verification = new Verification($client);
        $refundedCredits = $verification->deleteBulk('job_1');

        self::assertSame('/v1/verify/files/job_1', $history[0]['request']->getUri()->getPath());
        self::assertSame(2, $refundedCredits);
    }

    public function testGetVerificationCredits(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => ['balance' => 4820, 'lowCredits' => false],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $verification = new Verification($client);
        $credits = $verification->credits();

        self::assertSame('/v1/verification-credits', $history[0]['request']->getUri()->getPath());
        self::assertSame(4820, $credits->balance);
        self::assertFalse($credits->lowCredits);
    }

    public function testListVerificationCreditLedger(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'items' => [
                        [
                            'id' => 'entry_1',
                            'seq' => 9182,
                            'type' => 'CONSUME',
                            'creditsDelta' => -1,
                            'balanceAfter' => 4820,
                            'source' => 'single_api',
                            'status' => 'Valid',
                            'createdAt' => '2026-06-17T10:15:30.000Z',
                        ],
                    ],
                    'nextCursor' => '9181',
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $verification = new Verification($client);
        $ledger = $verification->ledger(['limit' => 1, 'cursor' => '9182']);
        $query = $this->getRequestQuery($history);

        self::assertSame('/v1/verification-credits/ledger', $history[0]['request']->getUri()->getPath());
        self::assertSame('1', $query['limit']);
        self::assertSame('9182', $query['cursor']);
        self::assertSame('9181', $ledger['nextCursor']);
        self::assertSame('CONSUME', $ledger['items'][0]->type);
        self::assertSame(-1, $ledger['items'][0]->creditsDelta);
    }

    private function createEmailFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mailglyph-verify-');

        if ($path === false) {
            self::fail('Unable to create temporary verification file.');
        }

        $csvPath = $path . '.csv';
        rename($path, $csvPath);
        file_put_contents($csvPath, "valid@example.com\ninvalid@example.com\n");

        return $csvPath;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 'job_1',
            'status' => 'PROCESSING',
            'originalFilename' => 'emails.csv',
            'fileSizeBytes' => 34,
            'localEmailCount' => 2,
            'reservedCredits' => 2,
            'confirmedEmailCount' => null,
            'valid' => 0,
            'invalid' => 0,
            'unknown' => 0,
            'catchall' => 0,
            'duplicates' => 0,
            'spamTrap' => 0,
            'toxicDomains' => 0,
            'readyForDownload' => false,
            'errorCode' => null,
            'errorMessage' => null,
            'createdAt' => '2026-06-18T10:12:30.000Z',
            'updatedAt' => '2026-06-18T10:13:10.000Z',
            'completedAt' => null,
            'creditUsed' => null,
            'lastValidationStatus' => null,
        ], $overrides);
    }
}
