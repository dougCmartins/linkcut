<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the busiest queries, checked with EXPLAIN on a large data set (docs/performance.md).
 * Some of them "cover" the query: every column it reads is in the index, so MySQL never reads the table rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            // Free times, slot conflicts and today's agenda (covering: only date, status and times are read).
            $table->index(['tenant_id', 'date', 'status', 'starts_at', 'ends_at'], 'booking_requests_tenant_day_index');
            // Pending requests and reminders: filtered by status and already in date/time order (no filesort).
            $table->index(['tenant_id', 'status', 'date', 'starts_at'], 'booking_requests_tenant_status_index');
            // "Meus horários" of a client, newest first.
            $table->index(['client_id', 'date', 'starts_at'], 'booking_requests_client_index');
        });
        // Dropped after the new one exists: MySQL may be using it for the tenant foreign key.
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'date', 'status']);
        });

        Schema::table('revenue_entries', function (Blueprint $table): void {
            // Month and history totals (covering: the sums never read the rows).
            $table->index(['tenant_id', 'date', 'price_in_cents', 'cost_in_cents'], 'revenue_entries_tenant_totals_index');
        });

        Schema::table('clients', function (Blueprint $table): void {
            // Hourly invitations: who was not invited nor opted out, old enough.
            $table->index(['registration_invited_at', 'emails_opted_out_at', 'created_at'], 'clients_to_invite_index');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->index('active');
        });

        Schema::table('images', function (Blueprint $table): void {
            // Daily cleanup of unused uploads (every tenant at once).
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->index(['tenant_id', 'date', 'status']);
        });
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropIndex('booking_requests_tenant_day_index');
            $table->dropIndex('booking_requests_tenant_status_index');
            $table->dropIndex('booking_requests_client_index');
        });
        Schema::table('revenue_entries', fn (Blueprint $table) => $table->dropIndex('revenue_entries_tenant_totals_index'));
        Schema::table('clients', fn (Blueprint $table) => $table->dropIndex('clients_to_invite_index'));
        Schema::table('tenants', fn (Blueprint $table) => $table->dropIndex(['active']));
        Schema::table('images', fn (Blueprint $table) => $table->dropIndex(['created_at']));
    }
};
