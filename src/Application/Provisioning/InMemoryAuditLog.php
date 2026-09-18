<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

final class InMemoryAuditLog implements AuditLog
{
    /** @var list<array{order_id:string,event:string,context:array<string,scalar|null>}> */
    private array $events = [];

    public function record(string $orderId, string $event, array $context = []): void
    {
        $this->events[] = ['order_id' => $orderId, 'event' => $event, 'context' => $context];
    }

    /** @return list<array{order_id:string,event:string,context:array<string,scalar|null>}> */
    public function events(): array
    {
        return $this->events;
    }
}
