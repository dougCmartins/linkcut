<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** The "elevated" button style (3D edge under the button) was removed: those barbershops get the flat "fill". */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('theme_settings')->where('button_style', 'elevated')->update(['button_style' => 'fill']);
    }

    public function down(): void
    {
        // Nothing to restore: "elevated" no longer exists.
    }
};
