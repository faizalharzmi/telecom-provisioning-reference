<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

use App\Domain\Provisioning\ProvisioningOrder;
use InvalidArgumentException;

final class ProvisioningService
{
    public function __construct(
        private readonly ProvisioningRepository $repository,
        private readonly ProvisioningQueue $queue,
        private readonly AuditLog $audit,
    ) {
    }

    public function create(string $customerReference, string $serviceReference, string $idempotencyKey): ProvisioningOrder
    {
        $existing = $this->repository->findByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            return $existing;
        }

        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Idempotency key cannot be empty.');
        }

        $order = new ProvisioningOrder(
            bin2hex(random_bytes(16)),
            $customerReference,
            $serviceReference,
            $idempotencyKey,
        );

        $this->repository->save($order);
        $this->audit->record($order->id(), 'provisioning.requested', [
            'customer_reference' => $customerReference,
            'service_reference' => $serviceReference,
        ]);
        $this->queue->publish($order->id());

        return $order;
    }
}
