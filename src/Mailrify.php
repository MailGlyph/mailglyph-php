<?php

declare(strict_types=1);

namespace Mailrify;

use Mailrify\Resources\Campaigns;
use Mailrify\Resources\Contacts;
use Mailrify\Resources\Emails;
use Mailrify\Resources\Events;
use Mailrify\Resources\Segments;

final class Mailrify
{
    public readonly Emails $emails;

    public readonly Events $events;

    public readonly Contacts $contacts;

    public readonly Campaigns $campaigns;

    public readonly Segments $segments;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(string $apiKey, array $config = [])
    {
        $version = $config['version'] ?? '0.2.0';
        $config['version'] = is_string($version) ? $version : '0.2.0';

        $httpClient = new HttpClient($apiKey, $config);

        $this->emails = new Emails($httpClient);
        $this->events = new Events($httpClient);
        $this->contacts = new Contacts($httpClient);
        $this->campaigns = new Campaigns($httpClient);
        $this->segments = new Segments($httpClient);
    }
}
