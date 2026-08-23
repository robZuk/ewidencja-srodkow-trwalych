<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
        .noborder td, .noborder th { border: none; padding: 2px 0; }
        .title { font-size: 15px; font-weight: bold; letter-spacing: 2px; text-align: center; }
        .lbl { font-size: 8px; color: #333; }
        .muted { color: #333; }
        .center { text-align: center; }
        .right { text-align: right; }
        .cb { display: inline-block; width: 10px; height: 10px; border: 1px solid #000; text-align: center; line-height: 10px; font-size: 8px; margin: 0 2px; }
        .tall { height: 46px; }
        .fill { border-bottom: 1px dotted #555; }
        .sec { background: #eee; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table>
        <tr>
            <td style="width: 42%;" class="tall">
                <div class="lbl" style="text-align:center; margin-top:34px;">(pieczęć jednostki)</div>
            </td>
            <td style="width: 58%; padding: 0;">
                <table class="noborder" style="width:100%;">
                    <tr><td colspan="2" class="title" style="padding:6px 0;">ZMIANA MIEJSCA UŻYTKOWANIA</td></tr>
                    <tr>
                        <td style="border-top:1px solid #000; padding:4px 6px;">Środka trwałego <span class="cb">X</span> MT</td>
                        <td style="border-top:1px solid #000; border-left:1px solid #000; padding:4px 6px;" rowspan="2">
                            <span style="font-size:14px; font-weight:bold;">NR</span>
                            <span class="fill">&nbsp;{{ $request->zmu_number ?? $request->id }}&nbsp;</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top:1px solid #000; padding:4px 6px;">Przedmiotu nietrwałego <span class="cb">&nbsp;</span> MN</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Nr inwentarzowy --}}
    <table style="border-top:none;">
        <tr>
            <td style="width:60%; border-top:none;">
                Dnia <span class="fill">&nbsp;{{ $request->created_at?->format('d.m.Y') }}&nbsp;</span> r. przeniesiono
            </td>
            <td style="width:40%; border-top:none;">
                Nr inwentarzowy<br>
                <strong>{{ $request->asset?->inventory_number ?? $request->asset_snapshot['inventory_number'] ?? '—' }}</strong>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div style="min-height:22px;">{{ $request->asset?->name ?? $request->asset_snapshot['name'] ?? '' }}</div>
                <div class="lbl center">(nazwa i charakterystyka)</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">Uzasadnienie <span class="fill">&nbsp;{{ $request->note }}&nbsp;</span></td>
        </tr>
    </table>

    {{-- Values + księgowość --}}
    @php($qty = (int) ($request->asset?->quantity ?? 1))
    @php($val = (float) ($request->asset?->value ?? $request->asset_snapshot['value'] ?? 0))
    <table style="border-top:none;">
        <tr>
            <th style="width:15%;" class="center">Jedn. miary</th>
            <th style="width:12%;" class="center">Ilość</th>
            <th style="width:18%;" class="center">Cena</th>
            <th style="width:20%;" class="center">Wartość</th>
            <td style="width:35%;" rowspan="2" class="center">
                <div style="margin-top:10px;">Księgowość</div>
                <div style="margin-top:14px;">Stanowisko kosztów</div>
            </td>
        </tr>
        <tr>
            <td class="center">szt.</td>
            <td class="center">{{ $qty }}</td>
            <td class="right">{{ $qty > 0 ? number_format($val / $qty, 2, ',', ' ') : '' }}</td>
            <td class="right">{{ number_format($val, 2, ',', ' ') }}</td>
        </tr>
    </table>

    {{-- Przeniesiono --}}
    <table style="border-top:none;">
        <tr><td colspan="2" class="sec">Przeniesiono</td></tr>
        <tr>
            <td style="width:65%;" class="tall">Skąd: {{ $request->sourceField?->label() ?? '—' }}</td>
            <td style="width:35%;" rowspan="2"></td>
        </tr>
        <tr>
            <td class="tall">Dokąd: {{ $request->targetField?->label() ?? '—' }}</td>
        </tr>
    </table>

    {{-- Signatures --}}
    <table style="border-top:none;">
        <tr>
            <th colspan="2" class="center">Zlecił</th>
            <th colspan="2" class="center">Przekazał</th>
            <th colspan="2" class="center">Przyjął</th>
            <th class="center">Data</th>
            <th class="center">Podpis</th>
        </tr>
        <tr>
            <td class="center lbl">Data</td><td class="center lbl">Podpis</td>
            <td class="center lbl">Data</td><td class="center lbl">Podpis</td>
            <td class="center lbl">Data</td><td class="center lbl">Podpis</td>
            <td></td><td></td>
        </tr>
        <tr class="tall">
            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
        </tr>
    </table>
</body>
</html>
