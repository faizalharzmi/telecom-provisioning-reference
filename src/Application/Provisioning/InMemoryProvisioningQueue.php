<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

final class InMemoryProvisioningQueue implements ProvisioningQueue
{
    /** @var list<string> */
    private array $messages = [];

    public function publish(string $orderId): void
    {
        $this->messages[] = $orderId;
    }

    /** @return list<string> */
    public function messages(): array
    {
        return $this->messages;
    }
}
