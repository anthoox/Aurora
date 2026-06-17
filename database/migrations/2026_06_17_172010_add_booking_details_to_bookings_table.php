<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('participants_count')->default(1)->after('service_id');
            $table->string('language')->nullable()->after('participants_count');
            $table->string('level')->nullable()->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'participants_count',
                'language',
                'level',
            ]);
        });
    }


};
