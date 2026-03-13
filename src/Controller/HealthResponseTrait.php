<?php

declare(strict_types=1);

namespace MyDashboard\Shared\Controller;

trait HealthResponseTrait
{
    /**
     * @return array{service: string, status: string, time: string}
     */
    protected function createHealthPayload(string $serviceName): array
    {
        return [
            'service' => $serviceName,
            'status'  => 'ok',
            'time'    => (new \DateTimeImmutable())->format('c'),
        ];
    }
}
