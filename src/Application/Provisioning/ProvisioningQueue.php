<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

interface ProvisioningQueue
{
    public function publish(string $orderId): void;
}
