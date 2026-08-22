<?php

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Powiadomienia" — transfer and liquidation requests with a three-step
 * approval workflow. Replaces the legacy `powiadomienia` table and its many
 * incremental ALTERs with a single clean schema + a status state machine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default(TransferType::Transfer->value);
            $table->string('status')->default(TransferStatus::Pending->value);

            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->json('asset_snapshot')->nullable();   // legacy dane_srodka

            $table->foreignId('source_field_id')->constrained('inventory_fields')->cascadeOnDelete();
            $table->foreignId('target_field_id')->nullable()->constrained('inventory_fields')->nullOnDelete();

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('inventory_accepted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('zmu_number')->nullable();     // numer druku ZMU
            $table->text('note')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'type']);
            $table->index('target_field_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_requests');
    }
};
