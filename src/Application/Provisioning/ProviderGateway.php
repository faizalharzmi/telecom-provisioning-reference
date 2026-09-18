<?php

declare(strict_types=1);

namespace App\Application\Provisioning;

use App\Domain\Provisioning\ProvisioningOrder;

interface ProviderGateway
{
    public function provision(ProvisioningOrder $order): ProviderResult;
}
