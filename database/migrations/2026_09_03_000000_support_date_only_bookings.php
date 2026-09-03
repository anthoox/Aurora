<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_mode')->default('time_slots')->after('source_id');
            $table->date('requested_date')->nullable()->after('booking_mode');
            $table->text('customer_message')->nullable()->after('notes');
            $table->dateTime('starts_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable(false)->change();
            $table->dropColumn([
                'booking_mode',
                'requested_date',
                'customer_message',
            ]);
        });
    }
};
