<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\Location;
use App\Models\TransferRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

beforeEach(function () {
    // A throwaway in-memory "legacy" database shaped like the old EKI schema.
    config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    DB::purge('legacy');

    $schema = Schema::connection('legacy');

    $schema->create('roles', function (Blueprint $t) {
        $t->integer('id')->primary();
        $t->string('name');
    });
    $schema->create('lokalizacje', function (Blueprint $t) {
        $t->increments('id');
        $t->string('Lokalizacja');
    });
    $schema->create('zasoby', function (Blueprint $t) {
        $t->string('Numer_Inwentarzowy');
        $t->string('Nazwa');
        $t->text('Opis')->nullable();
        $t->string('Numer_Dok_Zakupu')->nullable();
        $t->decimal('Wartosc', 10, 2)->nullable();
        $t->date('Data_Zakupu')->nullable();
        $t->date('Data_Likwidacji')->nullable();
        $t->integer('Ilosc')->nullable();
        $t->string('Srodek')->nullable();
        $t->string('Lokalizacja')->nullable();
        $t->integer('Numer_Pola_Spisowego');
        $t->string('Status')->nullable();
        $t->text('Komentarz')->nullable();
    });
    $schema->create('powiadomienia', function (Blueprint $t) {
        $t->increments('id');
        $t->string('typ')->nullable();
        $t->string('pole_spisowe_zrodlowe')->nullable();
        $t->string('pole_spisowe_docelowe')->nullable();
        $t->text('dane_srodka')->nullable();
        $t->string('status')->nullable();
        $t->text('notatka')->nullable();
        $t->timestamps();
    });

    DB::connection('legacy')->table('roles')->insert([
        ['id' => 1000000, 'name' => 'Dziekanat'],
        ['id' => 1000001, 'name' => 'Biblioteka'],
        ['id' => 999999, 'name' => 'Admin'],   // app role — must be skipped
    ]);

    DB::connection('legacy')->table('zasoby')->insert([
        'Numer_Inwentarzowy' => '001-001-01', 'Nazwa' => 'Laptop', 'Wartosc' => 3500.00,
        'Data_Zakupu' => '2020-01-15', 'Data_Likwidacji' => null, 'Ilosc' => 1, 'Srodek' => 'ST_NIS',
        'Lokalizacja' => 'Pokój 12', 'Numer_Pola_Spisowego' => 1000000, 'Status' => 'Dostępny',
    ]);
    DB::connection('legacy')->table('zasoby')->insert([
        'Numer_Inwentarzowy' => '002-002-02', 'Nazwa' => 'Rzutnik', 'Wartosc' => 1200.00,
        'Data_Zakupu' => '2018-06-01', 'Data_Likwidacji' => '2023-03-03', 'Ilosc' => 1, 'Srodek' => null,
        'Lokalizacja' => 'Pokój 12', 'Numer_Pola_Spisowego' => 1000001, 'Status' => 'Zlikwidowany',
    ]);
    DB::connection('legacy')->table('zasoby')->insert([ // orphaned — non-existent field, skipped
        'Numer_Inwentarzowy' => '003-003-03', 'Nazwa' => 'Sierota', 'Wartosc' => 10,
        'Data_Zakupu' => null, 'Data_Likwidacji' => null, 'Ilosc' => 1, 'Srodek' => null,
        'Lokalizacja' => null, 'Numer_Pola_Spisowego' => 555, 'Status' => 'Dostępny',
    ]);

    DB::connection('legacy')->table('powiadomienia')->insert([
        'typ' => 'przekazanie_srodka', 'pole_spisowe_zrodlowe' => '1000000',
        'pole_spisowe_docelowe' => '1000001', 'status' => 'oczekuje',
        'dane_srodka' => json_encode(['name' => 'Laptop']), 'notatka' => 'test',
        'created_at' => now(), 'updated_at' => now(),
    ]);
});

it('imports fields, locations, assets and transfers from the legacy db', function () {
    artisan('app:import-legacy')->assertSuccessful();

    expect(InventoryField::count())->toBe(2)                                  // app role skipped
        ->and(InventoryField::where('code', '999999')->exists())->toBeFalse()
        ->and(Asset::count())->toBe(2)                                        // orphan skipped
        ->and(Location::count())->toBe(1)                                     // deduped "Pokój 12"
        ->and(TransferRequest::count())->toBe(1);

    $liquidated = Asset::where('inventory_number', '002-002-02')->first();
    expect($liquidated->status)->toBe(AssetStatus::Liquidated)
        ->and($liquidated->liquidation_date)->not->toBeNull();
});

it('is idempotent across repeated runs', function () {
    artisan('app:import-legacy')->assertSuccessful();
    artisan('app:import-legacy')->assertSuccessful();

    expect(Asset::count())->toBe(2)
        ->and(InventoryField::count())->toBe(2);
});

it('previews counts on a dry run without writing', function () {
    artisan('app:import-legacy', ['--dry-run' => true])->assertSuccessful();

    expect(Asset::count())->toBe(0)
        ->and(InventoryField::count())->toBe(0);
});
