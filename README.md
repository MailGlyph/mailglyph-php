# Mailrify PHP SDK

[![CI](https://github.com/Mailrify/mailrify-php/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Mailrify/mailrify-php/actions/workflows/ci.yml)
[![Release Please](https://github.com/Mailrify/mailrify-php/actions/workflows/release-please.yml/badge.svg?branch=main)](https://github.com/Mailrify/mailrify-php/actions/workflows/release-please.yml)

Official Mailrify PHP SDK.

## Requirements

- PHP 8.1+

## Installation

```bash
composer require mailrify/mailrify-php
```

## Initialization

```php
<?php

declare(strict_types=1);

use Mailrify\Mailrify;

$client = new Mailrify('sk_your_api_key');

// Optional config
$client = new Mailrify('sk_your_api_key', [
    'baseUrl' => 'https://api.mailrify.com',
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
    'data' => ['name' => 'John'],
]);

// Verify email
$verification = $client->emails->verify('user@example.com');
var_dump($verification->valid);
var_dump($verification->isRandomInput);
```

## Events

```php
<?php

use Mailrify\Mailrify;

// Track event (public key only)
$tracker = new Mailrify('pk_your_public_key');
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

use Mailrify\Exceptions\AuthenticationException;
use Mailrify\Exceptions\NotFoundException;
use Mailrify\Exceptions\RateLimitException;
use Mailrify\Exceptions\ValidationException;

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
