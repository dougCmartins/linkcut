<?php

declare(strict_types=1);

namespace Domain\Tenant\Tests\Feature;

use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Exceptions\TenantMismatchException;
use Domain\Tenant\Exceptions\TenantNotResolvedException;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** A tenant-owned model that only exists in this test (the first real ones arrive in phase 2). */
final class TenantProbe extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_probes';

    protected $fillable = ['tenant_id', 'label'];
}

final class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('tenant_probes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('label');
            $table->timestamps();
        });
        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();
        $this->currentTenant()->run($this->tenantA->id, fn () => TenantProbe::query()->create(['label' => 'a']));
        $this->currentTenant()->run($this->tenantB->id, fn () => TenantProbe::query()->create(['label' => 'b']));
    }

    private function currentTenant(): CurrentTenant
    {
        return app(CurrentTenant::class);
    }

    public function test_queries_only_see_rows_of_the_current_tenant(): void
    {
        // Arrange
        $this->currentTenant()->set($this->tenantA->id);

        // Act
        $labels = TenantProbe::query()->pluck('label')->all();

        // Assert
        $this->assertSame(['a'], $labels);
    }

    public function test_querying_without_a_tenant_fails_closed(): void
    {
        // Arrange
        $this->currentTenant()->forget();

        // Assert
        $this->expectException(TenantNotResolvedException::class);

        // Act
        TenantProbe::query()->get();
    }

    public function test_new_rows_get_the_current_tenant_automatically(): void
    {
        // Arrange
        $this->currentTenant()->set($this->tenantB->id);

        // Act
        $probe = TenantProbe::query()->create(['label' => 'new']);

        // Assert
        $this->assertSame($this->tenantB->id, $probe->tenant_id);
    }

    public function test_writing_a_row_of_another_tenant_is_refused(): void
    {
        // Arrange
        $this->currentTenant()->set($this->tenantA->id);

        // Assert
        $this->expectException(TenantMismatchException::class);

        // Act
        TenantProbe::query()->create(['label' => 'intruder', 'tenant_id' => $this->tenantB->id]);
    }

    public function test_platform_reads_can_opt_out_explicitly(): void
    {
        // Arrange
        $this->currentTenant()->forget();

        // Act
        $count = TenantProbe::withoutTenantScope()->count();

        // Assert
        $this->assertSame(2, $count);
    }

    public function test_run_restores_the_previous_tenant(): void
    {
        // Arrange
        $this->currentTenant()->set($this->tenantA->id);

        // Act
        $insideLabels = $this->currentTenant()->run($this->tenantB->id, fn () => TenantProbe::query()->pluck('label')->all());

        // Assert
        $this->assertSame(['b'], $insideLabels);
        $this->assertSame($this->tenantA->id, $this->currentTenant()->id());
    }
}
