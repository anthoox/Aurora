<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('availability_policy', 20)
                ->default('blocking');

            $table->index(
                ['service_id', 'status', 'starts_at', 'ends_at'],
                'bookings_service_availability_index',
            );

            $table->index(
                [
                    'customer_id',
                    'service_id',
                    'booking_mode',
                    'status',
                    'requested_date',
                    'starts_at',
                ],
                'bookings_active_duplicate_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_active_duplicate_lookup_index');
            $table->dropIndex('bookings_service_availability_index');
            $table->dropColumn('availability_policy');
        });
    }
};
