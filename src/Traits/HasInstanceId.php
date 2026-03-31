<?php

declare(strict_types=1);

namespace MyDashboard\Shared\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Adds instanceId (VARCHAR 36 UUID) to any entity.
 * The owning class MUST carry #[ORM\HasLifecycleCallbacks].
 */
trait HasInstanceId
{
    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $instanceId = null;

    public function getInstanceId(): ?string
    {
        return $this->instanceId;
    }

    public function setInstanceId(?string $instanceId): static
    {
        $this->instanceId = $instanceId;
        return $this;
    }
}
