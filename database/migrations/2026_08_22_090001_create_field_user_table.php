<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership of users in inventory fields. A user who belongs to a field is the
 * "recipient" who accepts incoming transfers targeted at that field (step 1 of
 * the two-role approval).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_field_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_field_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'inventory_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_field_user');
    }
};
