<?php

declare(strict_types=1);

namespace App\Domain\Provisioning;

use InvalidArgumentException;
use LogicException;

final class ProvisioningOrder
{
    private ProvisioningState $state = ProvisioningState::PENDING;
    private int $attempts = 0;
    private ?string $providerReference = null;
    private ?string $failureReason = null;

    public function __construct(
        private readonly string $id,
        private readonly string $customerReference,
        private readonly string $serviceReference,
        private readonly string $idempotencyKey,
    ) {
        foreach ([$id, $customerReference, $serviceReference, $idempotencyKey] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('Provisioning identifiers cannot be empty.');
            }
        }
    }

    public function id(): string { return $this->id; }
    public function customerReference(): string { return $this->customerReference; }
    public function serviceReference(): string { return $this->serviceReference; }
    public function idempotencyKey(): string { return $this->idempotencyKey; }
    public function state(): ProvisioningState { return $this->state; }
    public function attempts(): int { return $this->attempts; }
    public function providerReference(): ?string { return $this->providerReference; }
    public function failureReason(): ?string { return $this->failureReason; }

    public function recordAttempt(): void
    {
        if ($this->state !== ProvisioningState::PENDING) {
            throw new LogicException('Only pending orders can be attempted.');
        }

        $this->attempts++;
    }

    public function markSubmitted(string $providerReference): void
    {
        $this->assertPending();
        if (trim($providerReference) === '') {
            throw new InvalidArgumentException('Provider reference cannot be empty.');
        }

        $this->providerReference = $providerReference;
        $this->state = ProvisioningState::SUBMITTED;
    }

    public function markActive(string $providerReference): void
    {
        $this->assertPending();
        if (trim($providerReference) === '') {
            throw new InvalidArgumentException('Provider reference cannot be empty.');
        }

        $this->providerReference = $providerReference;
        $this->state = ProvisioningState::ACTIVE;
    }

    public function markFailed(string $reason): void
    {
        $this->assertPending();
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Failure reason cannot be empty.');
        }

        $this->failureReason = $reason;
        $this->state = ProvisioningState::FAILED;
    }

    private function assertPending(): void
    {
        if ($this->state !== ProvisioningState::PENDING) {
            throw new LogicException('Only pending orders can change state.');
        }
    }
}
