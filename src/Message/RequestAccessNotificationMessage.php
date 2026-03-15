<?php

declare(strict_types=1);

namespace MyDashboard\Shared\Message;

final class RequestAccessNotificationMessage
{
    /**
     * @param array<string, mixed> $requester
     * @param array<int, array{id:string,email:string}> $recipients
     */
    public function __construct(
        private readonly array $requester,
        private readonly array $recipients,
    ) {
    }

    /** @return array<string, mixed> */
    public function getRequester(): array
    {
        return $this->requester;
    }

    /** @return array<int, array{id:string,email:string}> */
    public function getRecipients(): array
    {
        return $this->recipients;
    }
}
