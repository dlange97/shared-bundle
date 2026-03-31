<?php

declare(strict_types=1);

namespace MyDashboard\Shared\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Reads X-Instance-Id header (or instance_id query param) on every request
 * and stores the value in request attributes under key 'instanceId'.
 *
 * Routes whose path starts with any entry in $bypassPaths are exempt.
 * If no instance ID is provided on a non-exempt route, a 400 response is returned.
 */
readonly class InstanceRequestListener
{
    /** @param list<string> $bypassPaths Path prefixes that do NOT require an instance ID */
    public function __construct(private array $bypassPaths = [])
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        foreach ($this->bypassPaths as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        $instanceId = $request->headers->get('X-Instance-Id')
            ?? $request->query->get('instance_id');

        if ($instanceId === null || $instanceId === '') {
            $event->setResponse(
                new JsonResponse(['error' => 'Missing required X-Instance-Id header'], 400)
            );
            return;
        }

        $request->attributes->set('instanceId', $instanceId);
    }
}
