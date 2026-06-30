<?php

namespace App\Actions\Gateway;

use App\Models\Gateway;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetGatewayAction
{
    /**
     * @throws ModelNotFoundException
     */
    public function execute(int $gatewayId): Gateway
    {
        return Gateway::query()
            ->findOrFail($gatewayId);
    }
}
