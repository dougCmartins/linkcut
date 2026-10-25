<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A booking can have several services ("Corte + Barba"): their ids, the total price frozen when booking and an
 * optional note from the client. `service_id` keeps the first one; `service_name` holds the joined label.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->json('service_ids')->nullable()->after('service_id');
            $table->unsignedInteger('price_in_cents')->nullable()->after('duration_minutes');
            $table->string('client_notes', 300)->nullable()->after('client_email');
            $table->string('service_name', 120)->change();
        });

        // Bookings made before this keep their single service.
        DB::table('booking_requests')->whereNull('service_ids')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('booking_requests')->where('id', $row->id)->update(['service_ids' => json_encode([(int) $row->service_id])]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn(['service_ids', 'price_in_cents', 'client_notes']);
        });
    }
};
