<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Provisioning\InMemoryAuditLog;
use App\Application\Provisioning\InMemoryProvisioningQueue;
use App\Application\Provisioning\InMemoryProvisioningRepository;
use App\Application\Provisioning\PermanentProviderException;
use App\Application\Provisioning\ProviderGateway;
use App\Application\Provisioning\ProviderResult;
use App\Application\Provisioning\ProvisioningService;
use App\Application\Provisioning\ProvisioningWorker;
use App\Application\Provisioning\TransientProviderException;
use App\Domain\Provisioning\ProvisioningOrder;
use App\Domain\Provisioning\ProvisioningState;
use PHPUnit\Framework\TestCase;

final class ProvisioningWorkerTest extends TestCase
{
    public function testWorkerRetriesTransientFailureThenSubmits(): void
    {
        $repository = new InMemoryProvisioningRepository();
        $queue = new InMemoryProvisioningQueue();
        $audit = new InMemoryAuditLog();
        $service = new ProvisioningService($repository, $queue, $audit);
        $order = $service->create('customer-1', 'service-1', 'request-1');

        $calls = 0;
        $provider = new class($calls) implements ProviderGateway {
            public function __construct(private int &$calls) {}

            public function provision(ProvisioningOrder $order): ProviderResult
            {
                $this->calls++;
                if ($this->calls === 1) {
                    throw new TransientProviderException('Provider timeout.');
                }

                return new ProviderResult(ProvisioningState::SUBMITTED, 'provider-123');
            }
        };

        $result = (new ProvisioningWorker($repository, $provider, $audit))->handle($order->id());

        self::assertSame(ProvisioningState::SUBMITTED, $result->state());
        self::assertSame(2, $result->attempts());
        self::assertSame('provider-123', $result->providerReference());
    }

    public function testPermanentFailureIsRecordedAndNotRetried(): void
    {
        $repository = new InMemoryProvisioningRepository();
        $queue = new InMemoryProvisioningQueue();
        $audit = new InMemoryAuditLog();
        $service = new ProvisioningService($repository, $queue, $audit);
        $order = $service->create('customer-1', 'service-1', 'request-1');

        $calls = 0;
        $provider = new class($calls) implements ProviderGateway {
            public function __construct(private int &$calls) {}

            public function provision(ProvisioningOrder $order): ProviderResult
            {
                $this->calls++;
                throw new PermanentProviderException('Invalid service address.');
            }
        };

        $result = (new ProvisioningWorker($repository, $provider, $audit))->handle($order->id());

        self::assertSame(ProvisioningState::FAILED, $result->state());
        self::assertSame(1, $result->attempts());
        self::assertSame('Invalid service address.', $result->failureReason());
    }
}
