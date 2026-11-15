<?php

declare(strict_types=1);

namespace Domain\Finance\Controllers;

use Domain\Finance\Actions\ListHistory;
use Domain\Finance\Actions\ShowMonthToDate;
use Domain\Finance\Data\HistoryPageData;
use Domain\Finance\Data\HistoryQueryData;
use Domain\Finance\Data\MonthToDateData;

final class FinanceController
{
    public function history(HistoryQueryData $data, ListHistory $action): HistoryPageData
    {
        return $action->handle($data);
    }

    public function monthToDate(ShowMonthToDate $action): MonthToDateData
    {
        return $action->handle();
    }
}
