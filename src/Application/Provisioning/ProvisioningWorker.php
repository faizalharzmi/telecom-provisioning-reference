<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

use App\Domain\Provisioning\ProvisioningOrder;
use App\Domain\Provisioning\ProvisioningState;

final class ProvisioningWorker
{
    public function __construct(
        private readonly ProvisioningRepository $repository,
        private readonly ProviderGateway $provider,
        private readonly AuditLog $audit,
        private readonly int $maxAttempts = 3,
    ) {
    }

    public function handle(string $orderId): ProvisioningOrder
    {
        $order = $this->repository->get($orderId);
        if ($order->state() !== ProvisioningState::PENDING) {
            return $order;
        }

        while ($order->attempts() < $this->maxAttempts) {
            $order->recordAttempt();
            $this->audit->record($order->id(), 'provider.attempted', ['attempt' => $order->attempts()]);

            try {
                $result = $this->provider->provision($order);
                if ($result->state === ProvisioningState::ACTIVE) {
                    $order->markActive($result->providerReference);
                } else {
                    $order->markSubmitted($result->providerReference);
                }
                $this->audit->record($order->id(), 'provisioning.state_changed', ['state' => $order->state()->value]);
                $this->repository->save($order);

                return $order;
            } catch (TransientProviderException $exception) {
                $this->audit->record($order->id(), 'provider.retryable_failure', [
                    'attempt' => $order->attempts(),
                    'reason' => $exception->getMessage(),
                ]);
            } catch (PermanentProviderException $exception) {
                $order->markFailed($exception->getMessage());
                $this->audit->record($order->id(), 'provisioning.failed', ['reason' => $exception->getMessage()]);
                $this->repository->save($order);

                return $order;
            }
        }

        $order->markFailed('Provider did not succeed after the retry limit.');
        $this->audit->record($order->id(), 'provisioning.failed', ['reason' => $order->failureReason()]);
        $this->repository->save($order);

        return $order;
    }
}
