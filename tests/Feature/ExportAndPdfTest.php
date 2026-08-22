<?php

use App\Models\Asset;
use App\Models\TransferRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->inventory = User::factory()->create()->assignRole('inventory_section');
    $this->viewer = User::factory()->create()->assignRole('viewer');
    $this->editor = User::factory()->create()->assignRole('editor');
});

it('exports assets as CSV', function () {
    Asset::factory()->count(3)->create();

    actingAs($this->viewer);

    get(route('assets.export.csv'))
        ->assertOk()
        ->assertDownload('srodki-'.now()->format('Y-m-d').'.csv');
});

it('exports assets as XLSX', function () {
    Asset::factory()->count(3)->create();

    actingAs($this->viewer);

    get(route('assets.export.xlsx'))
        ->assertOk()
        ->assertDownload('srodki-'.now()->format('Y-m-d').'.xlsx');
});

it('generates a ZMU pdf for a transfer request', function () {
    $request = TransferRequest::factory()->create();

    actingAs($this->inventory);

    $response = get(route('druki.zmu.pdf', $request))->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('generates a liquidation pdf for a liquidation request', function () {
    $request = TransferRequest::factory()->liquidation()->create();

    actingAs($this->inventory);

    get(route('druki.likwidacja.pdf', $request))->assertOk();
});

it('returns 404 when the druk type does not match the request', function () {
    $transfer = TransferRequest::factory()->create();

    actingAs($this->inventory);

    get(route('druki.likwidacja.pdf', $transfer))->assertNotFound();
});

it('forbids an editor from generating druki', function () {
    $request = TransferRequest::factory()->create();

    actingAs($this->editor);

    get(route('druki.zmu.pdf', $request))->assertForbidden();
});
