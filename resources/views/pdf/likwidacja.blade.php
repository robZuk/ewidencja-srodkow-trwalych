<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #6b7280; font-size: 11px; }
        .box { border: 1px solid #d1d5db; border-radius: 6px; padding: 12px 14px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px 8px; vertical-align: top; }
        th { width: 38%; color: #6b7280; font-weight: normal; }
        .sign { margin-top: 56px; }
        .sign td { border-top: 1px solid #9ca3af; padding-top: 6px; text-align: center; color: #6b7280; width: 45%; }
        .spacer td { border: none; width: 10%; }
    </style>
</head>
<body>
    <h1>Protokół likwidacji środka trwałego</h1>
    <div class="muted">
        Dokument nr LIK-{{ $request->id }} · wygenerowano {{ now()->format('Y-m-d H:i') }}
    </div>

    <div class="box">
        <table>
            <tr><th>Numer inwentarzowy</th><td>{{ $request->asset?->inventory_number ?? $request->asset_snapshot['inventory_number'] ?? '—' }}</td></tr>
            <tr><th>Nazwa środka</th><td>{{ $request->asset?->name ?? $request->asset_snapshot['name'] ?? '—' }}</td></tr>
            <tr><th>Pole spisowe</th><td>{{ $request->sourceField?->label() ?? '—' }}</td></tr>
            <tr><th>Wartość</th><td>{{ number_format((float) ($request->asset?->value ?? $request->asset_snapshot['value'] ?? 0), 2, ',', ' ') }} zł</td></tr>
        </table>
    </div>

    <div class="box">
        <table>
            <tr><th>Zgłaszający</th><td>{{ $request->requester?->name ?? '—' }}</td></tr>
            <tr><th>Data zgłoszenia</th><td>{{ $request->created_at?->format('Y-m-d') }}</td></tr>
            <tr><th>Status</th><td>{{ $request->status->label() }}</td></tr>
            <tr><th>Uzasadnienie</th><td>{{ $request->note ?? '—' }}</td></tr>
        </table>
    </div>

    <table class="sign">
        <tr>
            <td>Podpis wnioskującego</td>
            <td class="spacer"></td>
            <td>Sekcja Inwentaryzacji</td>
        </tr>
    </table>
</body>
</html>
