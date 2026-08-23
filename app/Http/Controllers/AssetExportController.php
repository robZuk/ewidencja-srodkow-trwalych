<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetExportController extends Controller
{
    /** @var list<string> */
    private const HEADINGS = [
        'Numer inwentarzowy', 'Nazwa', 'Opis', 'Numer dok. zakupu', 'Wartość',
        'Data zakupu', 'Data likwidacji', 'Ilość', 'Typ', 'Lokalizacja',
        'Pole spisowe', 'Status',
    ];

    public function csv(Request $request): StreamedResponse
    {
        Gate::authorize('view assets');

        $filename = 'srodki-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');
            fprintf($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($handle, self::HEADINGS, ';');

            $this->assets($this->filters(request()))->chunk(500, function ($assets) use ($handle): void {
                foreach ($assets as $asset) {
                    fputcsv($handle, array_values($this->row($asset)), ';');
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function xlsx(Request $request): BinaryFileResponse
    {
        Gate::authorize('view assets');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Środki');
        $sheet->fromArray(self::HEADINGS, null, 'A1');
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);

        $rowNumber = 2;
        $this->assets($this->filters($request))->chunk(500, function ($assets) use ($sheet, &$rowNumber): void {
            foreach ($assets as $asset) {
                $sheet->fromArray(array_values($this->row($asset)), null, 'A'.$rowNumber);
                $rowNumber++;
            }
        });

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'assets').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return response()
            ->download($path, 'srodki-'.now()->format('Y-m-d').'.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * @param  array{search: ?string, status: ?string, field: ?int, type: ?string}  $filters
     * @return Builder<Asset>
     */
    private function assets(array $filters)
    {
        return Asset::query()
            ->with(['inventoryField', 'location'])
            ->search($filters['search'])
            ->withStatus($filters['status'])
            ->forField($filters['field'])
            ->withType($filters['type'])
            ->orderBy('inventory_number');
    }

    /** @return array{search: ?string, status: ?string, field: ?int, type: ?string} */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'field' => $request->integer('field') ?: null,
            'type' => $request->string('type')->toString() ?: null,
        ];
    }

    /** @return array<string, string> */
    private function row(Asset $asset): array
    {
        return [
            'inventory_number' => $asset->inventory_number,
            'name' => $asset->name,
            'description' => (string) $asset->description,
            'purchase_doc_number' => (string) $asset->purchase_doc_number,
            'value' => number_format((float) $asset->value, 2, ',', ''),
            'purchase_date' => $asset->purchase_date?->format('Y-m-d') ?? '',
            'liquidation_date' => $asset->liquidation_date?->format('Y-m-d') ?? '',
            'quantity' => (string) $asset->quantity,
            'asset_type' => (string) $asset->asset_type,
            'location' => (string) $asset->location?->name,
            'inventory_field' => (string) $asset->inventoryField?->label(),
            'status' => $asset->status->label(),
        ];
    }
}
