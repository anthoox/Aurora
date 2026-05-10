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
        Schema::table('service_source', function (Blueprint $table) {
            $table->text('description')->nullable()->after('source_id');
            $table->decimal('price', 8, 2)->nullable()->after('description');
            $table->boolean('is_active')->default(true)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('service_source', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'price',
                'is_active',
            ]);
        });
    }
};
