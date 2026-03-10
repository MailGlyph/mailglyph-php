# MailGlyph PHP SDK

[![CI](https://github.com/MailGlyph/mailglyph-php/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/MailGlyph/mailglyph-php/actions/workflows/ci.yml)
[![Release Please](https://github.com/MailGlyph/mailglyph-php/actions/workflows/release-please.yml/badge.svg?branch=main)](https://github.com/MailGlyph/mailglyph-php/actions/workflows/release-please.yml)

Official MailGlyph PHP SDK.

## Requirements

- PHP 8.1+

## Installation

```bash
composer require mailglyph/mailglyph-php
```

## Initialization

```php
<?php

declare(strict_types=1);

use MailGlyph\MailGlyph;

$client = new MailGlyph('sk_your_api_key');

// Optional config
$client = new MailGlyph('sk_your_api_key', [
    'baseUrl' => 'https://api.mailglyph.com',
    'timeout' => 30000,
]);
```

## Emails

```php
<?php

// Send email
$sendResult = $client->emails->send([
    'to' => 'user@example.com',
    'from' => ['name' => 'My App', 'email' => 'hello@myapp.com'],
    'subject' => 'Welcome!',
    'body' => '<h1>Hello {{name}}</h1>',
    'text' => 'Hello {{name}}',
    'data' => ['name' => 'John'],
]);

// HTML only (plain text auto-generated from body)
$client->emails->send([
    'to' => 'user@example.com',
    'from' => 'hello@myapp.com',
    'subject' => 'HTML only',
    'body' => '<h1>Hello</h1><p>This uses auto-generated text/plain.</p>',
]);

// HTML + text="" (opt out of text/plain part)
$client->emails->send([
    'to' => 'user@example.com',
    'from' => 'hello@myapp.com',
    'subject' => 'No text/plain',
    'body' => '<h1>Hello</h1><p>This is HTML only.</p>',
    'text' => '',
]);

// Verify email
$verification = $client->emails->verify('user@example.com');
var_dump($verification->valid);
var_dump($verification->isRandomInput);
```

## Events

```php
<?php

use MailGlyph\MailGlyph;

// Track event (public key only)
$tracker = new MailGlyph('pk_your_public_key');
$trackResult = $tracker->events->track([
    'email' => 'user@example.com',
    'event' => 'purchase',
    'data' => ['product' => 'Premium', 'amount' => 99],
]);

// List event names (secret key)
$eventNames = $client->events->listNames();
```

## Contacts

```php
<?php

$contactsPage = $client->contacts->list(['limit' => 50]);

$contact = $client->contacts->create([
    'email' => 'new@example.com',
    'data' => ['firstName' => 'John', 'plan' => 'premium'],
]);

$fetched = $client->contacts->get($contact->id);

$updated = $client->contacts->update($contact->id, [
    'subscribed' => false,
]);

$totalContacts = $client->contacts->count();

$client->contacts->delete($contact->id);
```

## Segments

```php
<?php

$segment = $client->segments->create([
    'name' => 'Premium Users',
    'condition' => [
        'logic' => 'AND',
        'groups' => [
            [
                'filters' => [
                    ['field' => 'data.plan', 'operator' => 'equals', 'value' => 'premium'],
                ],
            ],
        ],
    ],
    'trackMembership' => true,
]);

$segments = $client->segments->list();
$singleSegment = $client->segments->get($segment->id);

$updatedSegment = $client->segments->update($segment->id, [
    'name' => 'Premium Users v2',
]);

$members = $client->segments->listContacts($segment->id, ['page' => 1, 'pageSize' => 20]);

// Static segment membership management
$addResult = $client->segments->addStaticMembers($segment->id, [
    'alice@example.com',
    'bob@example.com',
]);
// ['added' => 2, 'notFound' => []]

$removeResult = $client->segments->removeStaticMembers($segment->id, [
    'alice@example.com',
]);
// ['removed' => 1]

$client->segments->delete($segment->id);
```

## Campaigns

```php
<?php

$campaign = $client->campaigns->create([
    'name' => 'Product Launch',
    'subject' => 'Introducing our new feature!',
    'body' => '<h1>Big news!</h1><p>Check out our latest feature.</p>',
    'from' => 'hello@myapp.com',
    'audienceType' => 'ALL',
]);

$campaignList = $client->campaigns->list(['status' => 'DRAFT']);
$singleCampaign = $client->campaigns->get($campaign->id);

$updatedCampaign = $client->campaigns->update($campaign->id, [
    'subject' => 'Updated subject',
]);

// Immediate send
$client->campaigns->send($campaign->id);

// Scheduled send
$client->campaigns->send($campaign->id, [
    'scheduledFor' => '2026-03-01T10:00:00Z',
]);

// Send test email
$client->campaigns->test($campaign->id, 'preview@myapp.com');

// Get stats
$stats = $client->campaigns->stats($campaign->id);

// Cancel
$client->campaigns->cancel($campaign->id);
```

## Error Handling

```php
<?php

use MailGlyph\Exceptions\AuthenticationException;
use MailGlyph\Exceptions\NotFoundException;
use MailGlyph\Exceptions\RateLimitException;
use MailGlyph\Exceptions\ValidationException;

try {
    $client->contacts->get('missing-id');
} catch (AuthenticationException $e) {
    // 401
} catch (ValidationException $e) {
    // 400
} catch (NotFoundException $e) {
    // 404
} catch (RateLimitException $e) {
    // 429
}
```
