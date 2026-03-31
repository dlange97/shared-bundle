<?php

declare(strict_types=1);

namespace MyDashboard\Shared\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Automatically stamps instanceId on every new entity that exposes
 * setInstanceId() — i.e. entities using the HasInstanceId trait.
 *
 * The instanceId is read from the current request attributes (set by
 * InstanceRequestListener before this listener fires).
 */
readonly class InstanceDoctrineListener
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!method_exists($entity, 'setInstanceId') || !method_exists($entity, 'getInstanceId')) {
            return;
        }

        // Only stamp when the entity does not already carry an instance ID
        if ($entity->getInstanceId() !== null) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $instanceId = $request->attributes->get('instanceId');
        if ($instanceId !== null && $instanceId !== '') {
            $entity->setInstanceId((string) $instanceId);
        }
    }
}
