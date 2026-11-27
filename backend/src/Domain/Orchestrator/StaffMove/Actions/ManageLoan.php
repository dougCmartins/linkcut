<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Actions;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Team\Actions\ExtendLoan;
use Domain\Team\Actions\MakeLoanPermanent;
use Domain\Team\Actions\ShowActiveLoan;
use Domain\Team\Data\ActiveLoanData;
use Domain\Team\Data\StaffMoveData;
use Domain\Team\Data\StaffMoveIdData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
use Illuminate\Support\Facades\Auth;

/** "Estender", "Encerrar agora" and "Tornar definitivo" on an active loan of the owner's brand (docs/decisoes/0003 P2). */
final class ManageLoan
{
    public function __construct(
        private readonly ShowActiveLoan $showLoan,
        private readonly ExtendLoan $extendLoan,
        private readonly MakeLoanPermanent $makePermanent,
        private readonly ReturnFromLoan $returnFromLoan,
        private readonly CurrentTenant $currentTenant,
        private readonly TenantClock $clock,
    ) {}

    public function extend(int $moveId, string $endsOn): StaffMoveData
    {
        $loan = $this->loan($moveId);
        $today = $this->currentTenant->run($loan->to_tenant_id, fn (): string => $this->clock->today()->toDateString());

        return $this->extendLoan->handle($loan, $endsOn, $today);
    }

    public function end(int $moveId): StaffMoveData
    {
        return $this->returnFromLoan->handle($this->loan($moveId), $this->owner()->id);
    }

    public function makePermanent(int $moveId): StaffMoveData
    {
        return $this->makePermanent->handle($this->loan($moveId), $this->owner()->id);
    }

    private function loan(int $moveId): ActiveLoanData
    {
        return $this->showLoan->handle(new StaffMoveIdData(id: $moveId, organization_id: (int) $this->owner()->organization_id));
    }

    private function owner(): User
    {
        $user = Auth::user();
        if (! $user instanceof User || $user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }

        return $user;
    }
}
