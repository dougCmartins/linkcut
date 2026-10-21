<?php

declare(strict_types=1);

namespace Domain\Settings\Tests\Feature;

use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ElevatedButtonStyleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_barbershops_that_used_the_elevated_style_get_flat_buttons(): void
    {
        // Arrange
        $elevated = Tenant::factory()->create();
        $outline = Tenant::factory()->create();
        foreach ([$elevated->id => 'elevated', $outline->id => 'outline'] as $tenantId => $style) {
            app(CurrentTenant::class)->run($tenantId, fn () => app(CreateDefaultSettings::class)->handle(new DefaultSettingsData));
            DB::table('theme_settings')->where('tenant_id', $tenantId)->update(['button_style' => $style]);
        }
        $migration = require database_path('migrations/2026_10_27_100000_replace_elevated_button_style.php');

        // Act
        $migration->up();

        // Assert
        $this->assertSame('fill', DB::table('theme_settings')->where('tenant_id', $elevated->id)->value('button_style'));
        $this->assertSame('outline', DB::table('theme_settings')->where('tenant_id', $outline->id)->value('button_style'));
    }
}
