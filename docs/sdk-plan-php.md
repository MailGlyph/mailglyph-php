# Mailrify PHP SDK Plan

> Shared spec: [sdk-plan.md](./sdk-plan.md) · Repo: [Mailrify/mailrify-php](https://github.com/Mailrify/mailrify-php) · Registry: [Packagist `mailrify/mailrify-php`](https://packagist.org/packages/mailrify/mailrify-php) · Min: PHP 8.1+

---

## Tech Stack

| Concern | Choice |
|---------|--------|
| Language | PHP 8.1+ |
| HTTP | Guzzle 7 |
| Testing | PHPUnit 10 |
| Linting | PHP_CodeSniffer (PSR-12) |
| Static analysis | PHPStan (level 8) |
| Autoloading | PSR-4 |

---

## Repository Structure

```
mailrify-php/
├── src/
│   ├── Mailrify.php             # Facade / entry point
│   ├── Client.php               # Config + initialization
│   ├── HttpClient.php           # Guzzle-based transport
│   ├── Errors/
│   │   ├── MailrifyException.php
│   │   ├── AuthenticationException.php
│   │   ├── ValidationException.php
│   │   ├── NotFoundException.php
│   │   ├── RateLimitException.php
│   │   └── ApiException.php
│   ├── Models/
│   │   ├── Contact.php
│   │   ├── Segment.php
│   │   ├── VerifyEmailResult.php
│   │   ├── SendEmailResult.php
│   │   ├── Campaign.php
│   │   └── TrackEventResult.php
│   └── Resources/
│       ├── Emails.php
│       ├── Events.php
│       ├── Contacts.php
│       ├── Campaigns.php
│       └── Segments.php
├── tests/
│   ├── Unit/
│   │   ├── EmailsTest.php
│   │   ├── EventsTest.php
│   │   ├── ContactsTest.php
│   │   ├── CampaignsTest.php
│   │   ├── SegmentsTest.php
│   │   └── HttpClientTest.php
│   └── Integration/
│       └── ApiTest.php
├── openapi.json
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── .php-cs-fixer.php
├── .github/
│   └── workflows/
│       ├── ci.yml
│       ├── release-please.yml
│       └── publish.yml
├── release-please-config.json
├── .release-please-manifest.json
├── AGENTS.md
├── README.md
├── LICENSE
└── CHANGELOG.md
```

---

## Key Models

```php
// src/Models/VerifyEmailResult.php
class VerifyEmailResult {
    public function __construct(
        public readonly string $email,
        public readonly bool $valid,
        public readonly bool $isDisposable,
        public readonly bool $isAlias,
        public readonly bool $isTypo,
        public readonly bool $isPlusAddressed,
        public readonly bool $isRandomInput,
        public readonly bool $isPersonalEmail,
        public readonly bool $domainExists,
        public readonly bool $hasWebsite,
        public readonly bool $hasMxRecords,
        public readonly ?string $suggestedEmail,
        public readonly array $reasons,
    ) {}
}

// src/Models/Contact.php
class Contact {
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly bool $subscribed,
        public readonly array $data,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}
}

// src/Models/Segment.php
class Segment {
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $condition,
        public readonly bool $trackMembership,
        public readonly int $memberCount,
    ) {}
}

// src/Models/Campaign.php
class Campaign {
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $subject,
        public readonly string $type,         // ALL | SEGMENT | FILTERED
        public readonly string $status,       // DRAFT | SCHEDULED | SENDING | SENT
        public readonly ?string $scheduledAt,
    ) {}
}
```

---

## Test Commands

| Scope | Command |
|-------|---------|
| Unit | `vendor/bin/phpunit --testsuite unit` |
| Integration | `MAILRIFY_API_KEY=sk_... vendor/bin/phpunit --testsuite integration` |
| Lint | `vendor/bin/phpcs --standard=PSR12 src/` |
| Static analysis | `vendor/bin/phpstan analyse` |

---

## `composer.json` key fields

```json
{
    "name": "mailrify/mailrify-php",
    "description": "Official Mailrify PHP SDK",
    "type": "library",
    "require": {
        "php": ">=8.1",
        "guzzlehttp/guzzle": "^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "phpstan/phpstan": "^1.10"
    },
    "autoload": {
        "psr-4": {
            "Mailrify\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Mailrify\\Tests\\": "tests/"
        }
    }
}
```

---

## Usage Examples (for README)

```php
<?php

use Mailrify\Mailrify;

$client = new Mailrify('sk_your_api_key');

// Send email
$result = $client->emails->send([
    'to' => 'user@example.com',
    'from' => ['name' => 'My App', 'email' => 'hello@myapp.com'],
    'subject' => 'Welcome!',
    'body' => '<h1>Hello {{name}}</h1>',
    'data' => ['name' => 'John'],
]);

// Verify email
$verification = $client->emails->verify('user@example.com');
echo $verification->data->valid;
echo $verification->data->isRandomInput;

// Track event (public key)
$tracker = new Mailrify('pk_your_public_key');
$tracker->events->track([
    'email' => 'user@example.com',
    'event' => 'purchase',
    'data' => ['product' => 'Premium', 'amount' => 99],
]);

// List event names
$eventNames = $client->events->listNames();

// Contacts CRUD
$contacts = $client->contacts->list(['limit' => 50]);
$contact = $client->contacts->create([
    'email' => 'new@example.com',
    'data' => ['firstName' => 'John', 'plan' => 'premium'],
]);
$client->contacts->update($contact->id, ['subscribed' => false]);
$client->contacts->delete($contact->id);

// Segments
$segment = $client->segments->create([
    'name' => 'Premium Users',
    'condition' => [
        'operator' => 'AND',
        'conditions' => [
            ['field' => 'data.plan', 'operator' => 'equals', 'value' => 'premium'],
        ],
    ],
    'trackMembership' => true,
]);
$members = $client->segments->listContacts($segment->id, ['page' => 1, 'pageSize' => 20]);

// Campaigns
$campaign = $client->campaigns->create([
    'name' => 'Product Launch',
    'subject' => 'Introducing our new feature!',
    'body' => '<h1>Big news!</h1><p>Check out our latest feature.</p>',
    'from' => 'hello@myapp.com',
    'audienceType' => 'ALL',
]);

// Schedule for later
$client->campaigns->send($campaign->id, [
    'scheduledFor' => '2026-03-01T10:00:00Z',
]);

// Send test email first
$client->campaigns->test($campaign->id, 'preview@myapp.com');

// Get stats
$stats = $client->campaigns->stats($campaign->id);

// Cancel
$client->campaigns->cancel($campaign->id);
```

---

## Release Automation

### `release-please-config.json`

```json
{
  "$schema": "https://raw.githubusercontent.com/googleapis/release-please/main/schemas/config.json",
  "packages": {
    ".": {
      "release-type": "php",
      "bump-minor-pre-major": true,
      "bump-patch-for-minor-pre-major": true
    }
  }
}
```

### `.release-please-manifest.json`

```json
{
  ".": "0.1.0"
}
```

### `.github/workflows/release-please.yml`

```yaml
name: Release Please

on:
  push:
    branches: [main]

permissions:
  contents: write
  pull-requests: write

jobs:
  release-please:
    runs-on: ubuntu-latest
    outputs:
      release_created: ${{ steps.release.outputs.release_created }}
      tag_name: ${{ steps.release.outputs.tag_name }}
    steps:
      - uses: googleapis/release-please-action@v4
        id: release
        with:
          release-type: php
```

> **Note:** Packagist auto-syncs from GitHub releases when a [webhook](https://packagist.org/about#how-to-update-packages) is configured. No explicit publish step needed — just connect the GitHub repo to Packagist and enable auto-updates.
