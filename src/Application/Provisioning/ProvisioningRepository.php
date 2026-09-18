<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

use App\Domain\Provisioning\ProvisioningOrder;

interface ProvisioningRepository
{
    public function save(ProvisioningOrder $order): void;

    public function get(string $orderId): ProvisioningOrder;

    public function findByIdempotencyKey(string $idempotencyKey): ?ProvisioningOrder;
}
