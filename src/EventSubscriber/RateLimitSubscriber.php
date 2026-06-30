<?php

declare(strict_types=1);

namespace MyDashboard\Shared\EventSubscriber;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Abstract rate-limiting subscriber using Symfony's sliding-window rate limiter.
 *
 * Each service subclass declares its own bypass paths and sensitive paths.
 * Blocked requests are logged into the local `rate_limit_log` table via DBAL.
 *
 * Runs at priority 10 (after InstanceRequestListener at 20, before security firewall at 8).
 */
abstract class RateLimitSubscriber implements EventSubscriberInterface
{
    private const DEFAULT_RETRY_AFTER_SECONDS = 60;

    /**
     * Path prefixes that are completely exempt from rate limiting (e.g. health checks).
     *
     * @var list<string>
     */
    protected array $bypassPaths = [];

    /**
     * Path prefixes that use the stricter (sensitive) limiter (e.g. login, register).
     *
     * @var list<string>
     */
    protected array $sensitivePaths = [];

    public function __construct(
        private readonly RateLimiterFactory $apiLimiter,
        private readonly RateLimiterFactory $apiSensitiveLimiter,
        private readonly Connection $connection,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        foreach ($this->bypassPaths as $bypassPath) {
            if (str_starts_with($path, $bypassPath)) {
                return;
            }
        }

        $ip          = $request->getClientIp() ?? '0.0.0.0';
        $isSensitive = false;

        foreach ($this->sensitivePaths as $sensitivePath) {
            if (str_starts_with($path, $sensitivePath)) {
                $isSensitive = true;
                break;
            }
        }

        $factory = $isSensitive ? $this->apiSensitiveLimiter : $this->apiLimiter;
        $limiter = $factory->create($ip);
        $limit   = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $this->logBlockedRequest($request, $isSensitive);

            $retryAfter = $limit->getRetryAfter();
            $retryIn    = $retryAfter !== null ? max(0, $retryAfter->getTimestamp() - time()) : self::DEFAULT_RETRY_AFTER_SECONDS;

            $event->setResponse(new JsonResponse(
                [
                    'error'      => 'Too many requests',
                    'message'    => 'Rate limit exceeded. Please slow down.',
                    'retryAfter' => $retryIn,
                ],
                Response::HTTP_TOO_MANY_REQUESTS,
                [
                    'X-RateLimit-Retry-After' => (string) $retryIn,
                    'Retry-After'             => (string) $retryIn,
                ],
            ));
        }
    }

    private function logBlockedRequest(
        \Symfony\Component\HttpFoundation\Request $request,
        bool $isSensitive,
    ): void {
        try {
            $this->connection->insert('rate_limit_log', [
                'ip'             => substr($request->getClientIp() ?? '0.0.0.0', 0, 45),
                'path'           => substr($request->getPathInfo(), 0, 500),
                'method'         => $request->getMethod(),
                'instance_id'    => $request->headers->get('X-Instance-Id'),
                'is_sensitive'   => $isSensitive ? 1 : 0,
                'user_agent'     => substr((string) $request->headers->get('User-Agent', ''), 0, 500),
                'created_at'     => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Never let logging failures affect the response.
        }
    }
}
