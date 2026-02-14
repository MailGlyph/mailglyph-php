<?php

declare(strict_types=1);

namespace Mailrify\Tests\Integration;

use Mailrify\Exceptions\MailrifyException;
use Mailrify\Exceptions\NotFoundException;
use Mailrify\HttpClient;
use Mailrify\Mailrify;
use PHPUnit\Framework\TestCase;
use Throwable;

// phpcs:disable Generic.Files.LineLength.TooLong
final class ApiTest extends TestCase
{
    public function testLocalApiIntegrationScenariosInOrder(): void
    {
        $apiKey = getenv('MAILRIFY_API_KEY');
        if ($apiKey === false || $apiKey === '') {
            self::markTestSkipped('Set MAILRIFY_API_KEY to run integration tests.');
        }

        $publicKey = getenv('MAILRIFY_PUBLIC_KEY');
        if ($publicKey === false || $publicKey === '') {
            self::markTestSkipped('Set MAILRIFY_PUBLIC_KEY to run integration tests.');
        }

        $baseUrl = getenv('MAILRIFY_BASE_URL');
        $domain = getenv('MAILRIFY_TEST_DOMAIN');
        $memberEmail = getenv('MAILRIFY_TEST_MEMBER_EMAIL');

        $resolvedBaseUrl = $baseUrl !== false && $baseUrl !== '' ? $baseUrl : 'https://api.mailrify.com';
        $resolvedDomain = $domain !== false && $domain !== '' ? $domain : 'mailrify.com';

        $secretClient = new Mailrify((string) $apiKey, ['baseUrl' => $resolvedBaseUrl]);
        $publicClient = new Mailrify((string) $publicKey, ['baseUrl' => $resolvedBaseUrl]);
        $cleanupHttpClient = new HttpClient((string) $apiKey, ['baseUrl' => $resolvedBaseUrl, 'maxRetries' => 1]);

        $suffix = sprintf('%d%s', time(), bin2hex(random_bytes(4)));
        $crudContactEmail = sprintf('sdk-integration-%s@%s', $suffix, $resolvedDomain);
        $testRecipient = sprintf('test@%s', $resolvedDomain);
        $campaignTestRecipient = $memberEmail !== false && $memberEmail !== '' ? $memberEmail : $testRecipient;
        $campaignSubjectUpdated = sprintf('Updated Test %s', $suffix);

        $createdContactId = null;
        $createdSegmentId = null;
        $createdCampaignId = null;

        try {
            $sendResult = $this->runStep('1. Email — Send', static function () use ($secretClient, $testRecipient): mixed {
                return $secretClient->emails->send([
                    'to' => $testRecipient,
                    'from' => 'sdk-test@mailrify.com',
                    'subject' => 'SDK Integration Test',
                    'body' => '<p>Test</p>',
                ]);
            });

            $emailRecord = $sendResult->emails[0] ?? [];
            $contactId = is_array($emailRecord['contact'] ?? null) ? (string) ($emailRecord['contact']['id'] ?? '') : '';
            self::assertNotSame('', $contactId, 'Step 1 failed: expected success response with a contact ID.');

            $verifyResult = $this->runStep('2. Email — Verify', static function () use ($secretClient, $testRecipient): mixed {
                return $secretClient->emails->verify($testRecipient);
            });
            self::assertSame($testRecipient, $verifyResult->email, 'Step 2 failed: verify email field mismatch.');

            $trackResult = $this->runStep('3. Events — Track with pk_*', static function () use ($publicClient, $testRecipient): mixed {
                return $publicClient->events->track([
                    'email' => $testRecipient,
                    'event' => 'sdk_test_event',
                ]);
            });
            self::assertNotSame('', $trackResult->event, 'Step 3 failed: expected event tracking response.');

            $eventNames = $this->runStep('4. Events — Get Names with sk_*', static function () use ($secretClient): mixed {
                return $secretClient->events->listNames();
            });
            self::assertIsArray($eventNames, 'Step 4 failed: expected event names array.');
            self::assertContains('sdk_test_event', $eventNames, 'Step 4 failed: sdk_test_event not found in event names.');

            $createdContact = $this->runStep('5.1 Contacts — Create', static function () use ($secretClient, $crudContactEmail): mixed {
                return $secretClient->contacts->create([
                    'email' => $crudContactEmail,
                    'data' => ['source' => 'sdk-test'],
                ]);
            });
            $createdContactId = $createdContact->id;
            self::assertSame($crudContactEmail, $createdContact->email, 'Step 5.1 failed: created contact email mismatch.');

            $fetchedContact = $this->runStep('5.2 Contacts — Get', static function () use ($secretClient, &$createdContactId): mixed {
                return $secretClient->contacts->get((string) $createdContactId);
            });
            self::assertSame((string) $createdContactId, $fetchedContact->id, 'Step 5.2 failed: fetched contact ID mismatch.');

            $updatedContact = $this->runStep('5.3 Contacts — Update', static function () use ($secretClient, &$createdContactId): mixed {
                return $secretClient->contacts->update((string) $createdContactId, [
                    'data' => ['source' => 'sdk-test', 'updated' => true],
                ]);
            });
            self::assertTrue((bool) ($updatedContact->data['updated'] ?? false), 'Step 5.3 failed: contact was not updated.');

            $listedContacts = $this->runStep('5.4 Contacts — List', static function () use ($secretClient): mixed {
                return $secretClient->contacts->list(['limit' => 20]);
            });
            self::assertGreaterThan(0, (int) ($listedContacts['total'] ?? 0), 'Step 5.4 failed: expected contacts total > 0.');

            $contactsCount = $this->runStep('5.5 Contacts — Count', static function () use ($secretClient): mixed {
                return $secretClient->contacts->count();
            });
            self::assertGreaterThan(0, $contactsCount, 'Step 5.5 failed: expected contacts count > 0.');

            $deleteContactResult = $this->runStep('5.6 Contacts — Delete', static function () use ($secretClient, &$createdContactId): mixed {
                return $secretClient->contacts->delete((string) $createdContactId);
            });
            self::assertTrue($deleteContactResult, 'Step 5.6 failed: expected delete to return true.');
            $createdContactId = null;

            $this->runStep('5.7 Contacts — Get deleted expects 404', static function () use ($secretClient, $createdContact): mixed {
                try {
                    $secretClient->contacts->get($createdContact->id);
                } catch (NotFoundException $exception) {
                    return true;
                }

                self::fail('Step 5.7 failed: expected NotFoundException for deleted contact.');
            });

            $campaign = $this->runStep('6.1 Campaigns — Create', static function () use ($secretClient, $suffix): mixed {
                return $secretClient->campaigns->create([
                    'name' => sprintf('SDK Test Campaign %s', $suffix),
                    'subject' => 'Test',
                    'body' => '<p>Test</p>',
                    'from' => 'sdk-test@mailrify.com',
                    'audienceType' => 'ALL',
                ]);
            });
            $createdCampaignId = $campaign->id;

            $fetchedCampaign = $this->runStep('6.2 Campaigns — Get', static function () use ($secretClient, &$createdCampaignId): mixed {
                return $secretClient->campaigns->get((string) $createdCampaignId);
            });
            self::assertSame('DRAFT', $fetchedCampaign->status, 'Step 6.2 failed: campaign is not DRAFT.');

            $updatedCampaign = $this->runStep('6.3 Campaigns — Update', static function () use ($secretClient, &$createdCampaignId, $campaignSubjectUpdated): mixed {
                return $secretClient->campaigns->update((string) $createdCampaignId, [
                    'subject' => $campaignSubjectUpdated,
                ]);
            });
            self::assertSame($campaignSubjectUpdated, $updatedCampaign->subject, 'Step 6.3 failed: campaign subject not updated.');

            $testSendResult = $this->runStep('6.4 Campaigns — Send test email', static function () use ($secretClient, &$createdCampaignId, $campaignTestRecipient): mixed {
                return $secretClient->campaigns->test((string) $createdCampaignId, $campaignTestRecipient);
            });
            self::assertTrue($testSendResult, 'Step 6.4 failed: campaign test send failed.');

            $stats = $this->runStep('6.5 Campaigns — Stats', static function () use ($secretClient, &$createdCampaignId): mixed {
                return $secretClient->campaigns->stats((string) $createdCampaignId);
            });
            self::assertIsArray($stats, 'Step 6.5 failed: expected campaign stats object.');

            $segment = $this->runStep('7.1 Segments — Create', static function () use ($secretClient, $suffix): mixed {
                return $secretClient->segments->create([
                    'name' => sprintf('SDK Test Segment %s', $suffix),
                    'condition' => [
                        'logic' => 'AND',
                        'groups' => [
                            [
                                'filters' => [
                                    ['field' => 'data.source', 'operator' => 'equals', 'value' => 'sdk-test'],
                                ],
                            ],
                        ],
                    ],
                    'trackMembership' => true,
                ]);
            });
            $createdSegmentId = $segment->id;

            $fetchedSegment = $this->runStep('7.2 Segments — Get', static function () use ($secretClient, &$createdSegmentId): mixed {
                return $secretClient->segments->get((string) $createdSegmentId);
            });
            self::assertSame((string) $createdSegmentId, $fetchedSegment->id, 'Step 7.2 failed: segment ID mismatch.');

            $updatedSegment = $this->runStep('7.3 Segments — Update', static function () use ($secretClient, &$createdSegmentId, $suffix): mixed {
                return $secretClient->segments->update((string) $createdSegmentId, [
                    'name' => sprintf('Updated SDK Test Segment %s', $suffix),
                ]);
            });
            self::assertStringStartsWith('Updated SDK Test Segment', $updatedSegment->name, 'Step 7.3 failed: segment name not updated.');

            $segments = $this->runStep('7.4 Segments — List', static function () use ($secretClient): mixed {
                return $secretClient->segments->list();
            });
            $segmentIds = array_map(static fn ($item): string => $item->id, $segments);
            self::assertContains((string) $createdSegmentId, $segmentIds, 'Step 7.4 failed: created segment not found in list.');

            $segmentContacts = $this->runStep('7.5 Segments — List contacts', static function () use ($secretClient, &$createdSegmentId): mixed {
                return $secretClient->segments->listContacts((string) $createdSegmentId, ['page' => 1, 'pageSize' => 10]);
            });
            self::assertIsArray($segmentContacts, 'Step 7.5 failed: expected paginated segment contacts result.');
            self::assertArrayHasKey('contacts', $segmentContacts, 'Step 7.5 failed: missing contacts key.');

            $deleteSegmentResult = $this->runStep('7.6 Segments — Delete', static function () use ($secretClient, &$createdSegmentId): mixed {
                return $secretClient->segments->delete((string) $createdSegmentId);
            });
            self::assertTrue($deleteSegmentResult, 'Step 7.6 failed: expected segment delete to return true.');
            $createdSegmentId = null;
        } finally {
            $this->cleanupResources($secretClient, $cleanupHttpClient, $createdContactId, $createdSegmentId, $createdCampaignId);
        }
    }

    /**
     * @param callable(): mixed $action
     */
    private function runStep(string $step, callable $action): mixed
    {
        $this->logStep(sprintf('[START] %s', $step));

        try {
            $result = $action();
        } catch (MailrifyException $exception) {
            $status = $exception->getStatusCode();
            $responseBody = json_encode($exception->getErrorData(), JSON_UNESCAPED_SLASHES);
            $this->fail(sprintf(
                "%s failed.\nHTTP status: %s\nResponse body: %s\nMessage: %s",
                $step,
                $status === null ? 'null' : (string) $status,
                $responseBody === false ? '{}' : $responseBody,
                $exception->getMessage()
            ));
        } catch (Throwable $exception) {
            $this->fail(sprintf('%s failed. Error: %s', $step, $exception->getMessage()));
        }

        $this->logStep(sprintf('[PASS] %s', $step));

        return $result;
    }

    private function logStep(string $message): void
    {
        fwrite(STDOUT, sprintf("[integration] %s\n", $message));
    }

    private function cleanupResources(
        Mailrify $secretClient,
        HttpClient $cleanupHttpClient,
        ?string $contactId,
        ?string $segmentId,
        ?string $campaignId
    ): void {
        if ($contactId !== null && $contactId !== '') {
            try {
                $this->logStep(sprintf('[CLEANUP] deleting contact %s', $contactId));
                $secretClient->contacts->delete($contactId);
            } catch (Throwable $exception) {
                $this->logStep(sprintf('[CLEANUP] contact delete failed: %s', $exception->getMessage()));
            }
        }

        if ($segmentId !== null && $segmentId !== '') {
            try {
                $this->logStep(sprintf('[CLEANUP] deleting segment %s', $segmentId));
                $secretClient->segments->delete($segmentId);
            } catch (Throwable $exception) {
                $this->logStep(sprintf('[CLEANUP] segment delete failed: %s', $exception->getMessage()));
            }
        }

        if ($campaignId !== null && $campaignId !== '') {
            try {
                $this->logStep(sprintf('[CLEANUP] attempting campaign delete %s', $campaignId));
                $cleanupHttpClient->request('DELETE', sprintf('/campaigns/%s', rawurlencode($campaignId)));
                $this->logStep(sprintf('[CLEANUP] campaign deleted %s', $campaignId));
            } catch (MailrifyException $exception) {
                $status = $exception->getStatusCode();
                $this->logStep(sprintf(
                    '[CLEANUP] campaign delete not supported or failed (status=%s, message=%s)',
                    $status === null ? 'null' : (string) $status,
                    $exception->getMessage()
                ));
            } catch (Throwable $exception) {
                $this->logStep(sprintf('[CLEANUP] campaign delete failed: %s', $exception->getMessage()));
            }
        }
    }
}
