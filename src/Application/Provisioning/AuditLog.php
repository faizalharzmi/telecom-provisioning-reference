<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

interface AuditLog
{
    /** @param array<string, scalar|null> $context */
    public function record(string $orderId, string $event, array $context = []): void;
}
