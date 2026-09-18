<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

use App\Domain\Provisioning\ProvisioningState;
use InvalidArgumentException;

final readonly class ProviderResult
{
    public function __construct(
        public ProvisioningState $state,
        public string $providerReference,
    ) {
        if (!in_array($state, [ProvisioningState::SUBMITTED, ProvisioningState::ACTIVE], true)) {
            throw new InvalidArgumentException('A provider result must be submitted or active.');
        }
        if (trim($providerReference) === '') {
            throw new InvalidArgumentException('Provider reference cannot be empty.');
        }
    }
}
