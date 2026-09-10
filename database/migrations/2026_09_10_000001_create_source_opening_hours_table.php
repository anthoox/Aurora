<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(
                ['source_id', 'day_of_week', 'is_active'],
                'source_opening_hours_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_opening_hours');
    }
};
