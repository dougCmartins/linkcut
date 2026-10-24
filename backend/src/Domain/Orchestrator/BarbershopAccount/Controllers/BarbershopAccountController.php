<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BarbershopAccount\Controllers;

use Domain\Orchestrator\BarbershopAccount\Actions\DeleteBarbershopAccount;
use Domain\Orchestrator\BarbershopAccount\Actions\ExportBarbershopData;
use Domain\Orchestrator\BarbershopAccount\Data\DeleteBarbershopData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class BarbershopAccountController
{
    public function export(ExportBarbershopData $action): JsonResponse
    {
        return response()->json($action->handle(), 200, [
            'Content-Disposition' => 'attachment; filename="linkcut-meus-dados.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function destroy(DeleteBarbershopData $data, DeleteBarbershopAccount $action): Response
    {
        $action->handle($data);

        return response()->noContent();
    }
}
