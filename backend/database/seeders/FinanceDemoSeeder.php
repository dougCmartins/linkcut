<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Domain\Finance\Models\FixedExpense;
use Domain\Finance\Models\OneOffExpense;
use Domain\Finance\Models\RevenueEntry;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Demo history (~2 years of appointments) and expenses for one tenant, only when it has none yet.
 * Each day is generated from a seed made of its date, so a re-seed gives the same numbers.
 */
final class FinanceDemoSeeder extends Seeder
{
    private const HISTORY_DAYS = 730;

    private const OPENING_MINUTES = 9 * 60;

    private const SLOT_MINUTES = 45;

    /** name, price, cost, weight */
    private const SERVICES = [
        ['Corte', 5000, 600, 45],
        ['Barba', 4000, 400, 20],
        ['Corte + Barba', 8000, 1000, 25],
        ['Pigmentação', 6550, 1500, 10],
    ];

    private const CLIENTS = [
        'Lucas Andrade', 'Bruno Lima', 'Pedro Martins', 'Gabriel Souza', 'Rafael Nunes', 'Mateus Rocha', 'Thiago Alves',
        'Felipe Barros', 'Diego Ramos', 'André Costa', 'João Pereira', 'Caio Mendes', 'Vinícius Lopes', 'Gustavo Reis',
        'Leonardo Dias', 'Rodrigo Pinto', 'Eduardo Freitas', 'Marcelo Teixeira', 'Henrique Castro', 'Daniel Moreira',
        'Igor Carvalho', 'Samuel Araújo', 'Renan Gomes', 'Otávio Melo', 'Murilo Cardoso', 'Arthur Ribeiro',
    ];

    public function forTenant(Tenant $tenant): void
    {
        app(CurrentTenant::class)->run($tenant->id, function () use ($tenant): void {
            $now = app(TenantClock::class)->now();
            if (! RevenueEntry::query()->exists()) {
                $this->history($tenant->id, $now);
            }
            if (! FixedExpense::query()->withTrashed()->exists() && ! OneOffExpense::query()->withTrashed()->exists()) {
                $this->expenses($now);
            }
        });
    }

    private function history(int $tenantId, CarbonImmutable $now): void
    {
        $rows = [];
        for ($offset = 0; $offset < self::HISTORY_DAYS; $offset++) {
            array_push($rows, ...$this->day($tenantId, $now->subDays($offset), $now));
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            RevenueEntry::query()->insert($chunk);
        }
    }

    /** @return list<array<string, mixed>> */
    private function day(int $tenantId, CarbonImmutable $day, CarbonImmutable $now): array
    {
        if ($day->isSunday()) {
            return [];
        }
        mt_srand(crc32($day->toDateString()));
        $count = $day->isSaturday() ? mt_rand(3, 6) : mt_rand(4, 8);
        $slots = range(0, $day->isSaturday() ? 6 : 12);
        shuffle($slots);
        $slots = array_slice($slots, 0, $count);
        sort($slots);

        $rows = [];
        foreach ($slots as $slot) {
            $minutes = self::OPENING_MINUTES + $slot * self::SLOT_MINUTES;
            if ($day->isSameDay($now) && $minutes > $now->hour * 60 + $now->minute) {
                continue;
            }
            [$service, $price, $cost] = $this->pickService();
            $rows[] = [
                'tenant_id' => $tenantId,
                'date' => $day->toDateString(),
                'time' => sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60),
                'client_name' => self::CLIENTS[mt_rand(0, count(self::CLIENTS) - 1)],
                'service_name' => $service,
                'price_in_cents' => $price,
                'cost_in_cents' => $cost,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    /** @return array{0: string, 1: int, 2: int} */
    private function pickService(): array
    {
        $target = mt_rand(1, array_sum(array_column(self::SERVICES, 3)));
        foreach (self::SERVICES as [$name, $price, $cost, $weight]) {
            $target -= $weight;
            if ($target <= 0) {
                return [$name, $price, $cost];
            }
        }

        return [self::SERVICES[0][0], self::SERVICES[0][1], self::SERVICES[0][2]];
    }

    private function expenses(CarbonImmutable $now): void
    {
        FixedExpense::query()->create(['name' => 'Aluguel da cadeira', 'amount_in_cents' => 150_000, 'due_day' => 5]);
        FixedExpense::query()->create(['name' => 'Internet', 'amount_in_cents' => 12_000, 'due_day' => 10]);
        FixedExpense::query()->create(['name' => 'DAS (MEI)', 'amount_in_cents' => 7_590, 'due_day' => 20]);

        $dayOfMonth = fn (int $day): string => $now->setDay(min($day, $now->day))->toDateString();
        OneOffExpense::query()->create(['description' => 'Lâminas e pomadas', 'amount_in_cents' => 21_000, 'date' => $dayOfMonth(2)]);
        OneOffExpense::query()->create(['description' => 'Toalhas descartáveis', 'amount_in_cents' => 8_500, 'date' => $dayOfMonth(12)]);
    }
}
