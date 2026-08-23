<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #000; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 6px 8px; vertical-align: middle; }
        td.top, th.top { vertical-align: top; }
        .title { font-size: 15px; font-weight: bold; letter-spacing: 3px; text-align: center; }
        .lbl { font-size: 8px; color: #444; }
        .val { font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .sec { background: #e6e6e6; font-weight: bold; text-align: center; letter-spacing: 1px; }
        .cb { display: inline-block; width: 11px; height: 11px; border: 1px solid #000; text-align: center; line-height: 11px; font-size: 9px; margin: 0 3px; vertical-align: middle; }
        .line { border-bottom: 1px solid #000; display: inline-block; min-width: 90px; padding: 0 4px; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table>
        <tr>
            <td class="top" style="width: 40%; height: 78px;">
                <div class="lbl center" style="margin-top: 58px;">(pieczęć jednostki)</div>
            </td>
            <td style="width: 60%; padding: 0;">
                <table>
                    <tr>
                        <td colspan="2" class="title" style="border: none; border-bottom: 1px solid #000; padding: 8px;">
                            ZMIANA MIEJSCA UŻYTKOWANIA
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; border-right: 1px solid #000; width: 62%;">
                            <div>Środka trwałego <span class="cb">X</span> MT</div>
                            <div style="margin-top: 5px;">Przedmiotu nietrwałego <span class="cb">&nbsp;</span> MN</div>
                        </td>
                        <td class="center" style="border: none;">
                            <span class="title" style="letter-spacing: 1px;">NR</span>
                            <div style="margin-top: 6px;"><span class="line val">{{ $request->zmu_number ?? $request->id }}</span></div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Asset --}}
    <table style="border-top: none;">
        <tr>
            <td style="width: 60%; border-top: none;">
                Dnia <span class="line val">{{ $request->created_at?->format('d.m.Y') }}</span> r. przeniesiono
            </td>
            <td style="width: 40%; border-top: none;">
                <span class="lbl">Nr inwentarzowy</span><br>
                <span class="val">{{ $request->asset?->inventory_number ?? $request->asset_snapshot['inventory_number'] ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="top" style="height: 34px;">
                <span class="val">{{ $request->asset?->name ?? $request->asset_snapshot['name'] ?? '' }}</span>
                <div class="lbl center" style="margin-top: 14px;">(nazwa i charakterystyka)</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">Uzasadnienie: <span class="val">{{ $request->note }}</span></td>
        </tr>
    </table>

    {{-- Values + accounting --}}
    @php($qty = (int) ($request->asset?->quantity ?? 1))
    @php($val = (float) ($request->asset?->value ?? $request->asset_snapshot['value'] ?? 0))
    <table style="border-top: none;">
        <tr>
            <th style="width: 15%;">Jedn. miary</th>
            <th style="width: 12%;">Ilość</th>
            <th style="width: 19%;">Cena</th>
            <th style="width: 19%;">Wartość</th>
            <td style="width: 35%;" rowspan="2" class="center">
                <div>Księgowość</div>
                <div style="margin-top: 18px;">Stanowisko kosztów</div>
            </td>
        </tr>
        <tr style="height: 26px;">
            <td class="center">szt.</td>
            <td class="center val">{{ $qty }}</td>
            <td class="right">{{ $qty > 0 ? number_format($val / $qty, 2, ',', ' ') : '' }}</td>
            <td class="right val">{{ number_format($val, 2, ',', ' ') }}</td>
        </tr>
    </table>

    {{-- Moved from / to --}}
    <table style="border-top: none;">
        <tr><td colspan="2" class="sec">Przeniesiono</td></tr>
        <tr>
            <td class="top" style="width: 65%; height: 42px;"><span class="lbl">Skąd</span><br><span class="val">{{ $request->sourceField?->label() ?? '—' }}</span></td>
            <td style="width: 35%;" rowspan="2"></td>
        </tr>
        <tr>
            <td class="top" style="height: 42px;"><span class="lbl">Dokąd</span><br><span class="val">{{ $request->targetField?->label() ?? '—' }}</span></td>
        </tr>
    </table>

    {{-- Signatures --}}
    <table style="border-top: none;">
        <tr>
            <th colspan="2">Zlecił</th>
            <th colspan="2">Przekazał</th>
            <th colspan="2">Przyjął</th>
            <th style="width: 13%;">Data</th>
            <th style="width: 15%;">Podpis</th>
        </tr>
        <tr class="lbl center">
            <td>Data</td><td>Podpis</td>
            <td>Data</td><td>Podpis</td>
            <td>Data</td><td>Podpis</td>
            <td></td><td></td>
        </tr>
        <tr style="height: 40px;">
            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
        </tr>
    </table>
</body>
</html>
