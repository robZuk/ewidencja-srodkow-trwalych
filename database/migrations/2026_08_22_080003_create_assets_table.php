<?php

use App\Enums\AssetStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Zasoby" — the assets themselves. Replaces the legacy `zasoby` table, whose
 * primary key was a concatenated string; here it is a normal auto-increment id
 * with a composite unique key on (inventory_number, inventory_field_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_number');                 // Numer_Inwentarzowy
            $table->string('name');                             // Nazwa
            $table->text('description')->nullable();            // Opis
            $table->string('purchase_doc_number')->nullable();  // Numer_Dok_Zakupu
            $table->decimal('value', 12, 2)->default(0);        // Wartosc
            $table->date('purchase_date')->nullable();          // Data_Zakupu
            $table->date('liquidation_date')->nullable();       // Data_Likwidacji
            $table->unsignedInteger('quantity')->default(1);    // Ilosc
            $table->string('asset_type')->nullable();           // Srodek
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_field_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(AssetStatus::Available->value);
            $table->text('comment')->nullable();                // Komentarz
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['inventory_number', 'inventory_field_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
