<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAccount\Controllers;

use Domain\Client\Data\ClientPasswordConfirmationData;
use Domain\Orchestrator\ClientAccount\Actions\DeleteClientAccount;
use Illuminate\Http\Response;

final class ClientAccountController
{
    public function destroy(ClientPasswordConfirmationData $data, DeleteClientAccount $action): Response
    {
        $action->handle($data);

        return response()->noContent();
    }
}
