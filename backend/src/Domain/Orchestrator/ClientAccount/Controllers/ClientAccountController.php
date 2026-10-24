<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAccount\Controllers;

use Domain\Client\Data\ClientPasswordConfirmationData;
use Domain\Orchestrator\ClientAccount\Actions\DeleteClientAccount;
use Domain\Orchestrator\ClientAccount\Actions\ExportClientData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class ClientAccountController
{
    public function export(ExportClientData $action): JsonResponse
    {
        return response()->json($action->handle(), 200, [
            'Content-Disposition' => 'attachment; filename="linkcut-meus-dados.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function destroy(ClientPasswordConfirmationData $data, DeleteClientAccount $action): Response
    {
        $action->handle($data);

        return response()->noContent();
    }
}
