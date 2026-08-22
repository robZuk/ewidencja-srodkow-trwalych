<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Pola spisowe" — the inventory fields assets are assigned to. In the legacy
 * system this was (mis)stored in the `roles` table; here it is a first-class table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_fields', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();   // legacy Numer_Pola_Spisowego
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_fields');
    }
};
