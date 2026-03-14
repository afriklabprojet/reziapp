<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LeaseContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaseContractSigned
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly LeaseContract $contract,
    ) {}
}
