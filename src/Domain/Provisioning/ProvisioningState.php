<?php

declare(strict_types=1);

namespace App\Domain\Provisioning;

enum ProvisioningState: string
{
    case PENDING = 'pending';
    case SUBMITTED = 'submitted';
    case ACTIVE = 'active';
    case FAILED = 'failed';
}
