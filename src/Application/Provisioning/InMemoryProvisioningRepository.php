<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

use App\Domain\Provisioning\ProvisioningOrder;
use RuntimeException;

final class InMemoryProvisioningRepository implements ProvisioningRepository
{
    /** @var array<string, ProvisioningOrder> */
    private array $orders = [];

    public function save(ProvisioningOrder $order): void
    {
        $this->orders[$order->id()] = $order;
    }

    public function get(string $orderId): ProvisioningOrder
    {
        if (!isset($this->orders[$orderId])) {
            throw new RuntimeException('Provisioning order not found.');
        }

        return $this->orders[$orderId];
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?ProvisioningOrder
    {
        foreach ($this->orders as $order) {
            if ($order->idempotencyKey() === $idempotencyKey) {
                return $order;
            }
        }

        return null;
    }
}
