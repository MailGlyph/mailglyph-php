<?php

declare(strict_types=1);

namespace MailGlyph;

use MailGlyph\Resources\Campaigns;
use MailGlyph\Resources\Contacts;
use MailGlyph\Resources\Emails;
use MailGlyph\Resources\Events;
use MailGlyph\Resources\Segments;
use MailGlyph\Resources\Templates;
use MailGlyph\Resources\Verification;

final class MailGlyph
{
    public readonly Emails $emails;

    public readonly Events $events;

    public readonly Contacts $contacts;

    public readonly Campaigns $campaigns;

    public readonly Templates $templates;

    public readonly Segments $segments;

    public readonly Verification $verification;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(string $apiKey, array $config = [])
    {
        $version = $config['version'] ?? '0.2.0';
        $config['version'] = is_string($version) ? $version : '0.2.0';

        $httpClient = new HttpClient($apiKey, $config);

        $this->verification = new Verification($httpClient);
        $this->emails = new Emails($httpClient, $this->verification);
        $this->events = new Events($httpClient);
        $this->contacts = new Contacts($httpClient);
        $this->campaigns = new Campaigns($httpClient);
        $this->templates = new Templates($httpClient);
        $this->segments = new Segments($httpClient);
    }
}
