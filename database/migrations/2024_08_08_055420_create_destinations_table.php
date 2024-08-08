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
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->timestamp('startAt');
            $table->timestamp('endAt');
            $table->text('note')->nullable();
            $table->enum('vehicle', ['car', 'motorcycle', 'bus', 'train', 'plane']);
            $table->unsignedBigInteger('travel_plan_id');
            $table->unsignedBigInteger('detail_location_id');
            $table->foreign('travel_plan_id')->references('id')->on('travel_plans')->onDelete('cascade');
            $table->foreign('detail_location_id')->references('id')->on('detail_locations')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
