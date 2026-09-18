<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Provisioning\InMemoryAuditLog;
use App\Application\Provisioning\InMemoryProvisioningQueue;
use App\Application\Provisioning\InMemoryProvisioningRepository;
use App\Application\Provisioning\ProvisioningService;
use PHPUnit\Framework\TestCase;

final class ProvisioningServiceTest extends TestCase
{
    public function testDuplicateIdempotencyKeyReturnsTheOriginalOrderAndPublishesOnce(): void
    {
        $repository = new InMemoryProvisioningRepository();
        $queue = new InMemoryProvisioningQueue();
        $audit = new InMemoryAuditLog();
        $service = new ProvisioningService($repository, $queue, $audit);

        $first = $service->create('customer-1', 'service-1', 'request-1');
        $second = $service->create('customer-1', 'service-1', 'request-1');

        self::assertSame($first->id(), $second->id());
        self::assertCount(1, $queue->messages());
        self::assertSame('provisioning.requested', $audit->events()[0]['event']);
    }
}
