<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail for asset changes. Replaces the legacy `modyfikacje_system`
 * table and the audit logic that used to live inside the Zasoby model's boot() method;
 * here it is written by a dedicated AssetObserver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('event');                 // created | updated | deleted
            $table->string('field')->nullable();     // changed column (for updates)
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable(); // denormalised for display
            $table->timestamp('created_at')->nullable();

            $table->index(['asset_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_activities');
    }
};
