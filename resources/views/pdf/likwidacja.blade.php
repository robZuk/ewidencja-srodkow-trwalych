<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #000; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        td.mid, th.mid { vertical-align: middle; }
        .title { font-size: 17px; font-weight: bold; letter-spacing: 6px; }
        .lbl { font-size: 8px; color: #444; }
        .val { font-weight: bold; }
        .center { text-align: center; }
        .sec { background: #e6e6e6; font-weight: bold; text-align: center; letter-spacing: 1px; }
        .cb { display: inline-block; width: 11px; height: 11px; border: 1px solid #000; text-align: center; line-height: 11px; font-size: 9px; margin: 0 3px; vertical-align: middle; }
        .line { border-bottom: 1px solid #000; display: inline-block; min-width: 70px; padding: 0 4px; }
        .cap { font-size: 8px; color: #444; text-align: center; }
    </style>
</head>
<body>
    @php($assetName = $request->asset?->name ?? $request->asset_snapshot['name'] ?? '—')
    @php($assetNo = $request->asset?->inventory_number ?? $request->asset_snapshot['inventory_number'] ?? '—')
    @php($qty = (int) ($request->asset?->quantity ?? 1))

    {{-- LT form --}}
    <table>
        <tr>
            <td class="mid" style="width: 62%; height: 52px;">
                <div class="title">L I K W I D A C J A</div>
                <div style="margin-top: 8px;">
                    Środka trwałego <span class="cb">X</span> LT
                    &nbsp;&nbsp;<span class="title" style="font-size: 15px; letter-spacing: 1px;">NR</span>
                    <span class="line val">{{ $request->zmu_number ?? $request->id }}</span>
                </div>
                <div style="margin-top: 5px;">Przedmiotu nietrwałego <span class="cb">&nbsp;</span> LN</div>
            </td>
            <td class="mid" style="width: 38%;">
                <div>Komórka organizacyjna</div>
                <div style="margin-top: 16px;">Symbol kosztów</div>
            </td>
        </tr>
        <tr>
            <th style="width: 62%;">Nazwa środka trwałego – przedmiotu nietrwałego</th>
            <th style="width: 38%;">Nr(y) inwentarzowe(y)</th>
        </tr>
        <tr>
            <td style="height: 70px;">
                <span class="val">{{ $assetName }}</span>
                <div style="margin-top: 44px;">Ilość sztuk <span class="line val">{{ $qty }}</span></div>
            </td>
            <td class="val">{{ $assetNo }}</td>
        </tr>
        <tr>
            <td colspan="2" style="height: 70px;">
                Orzeczenie Komisji Likwidacyjnej:
                <div class="val" style="margin-top: 6px;">{{ $request->note }}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 62%; height: 64px;">
                <div class="center">Komisja likwidacyjna <span class="lbl">(podpisy)</span></div>
                <div style="margin-top: 10px;">Data: <span class="line">&nbsp;</span></div>
                <div style="margin-top: 22px;"><span class="line" style="min-width: 90%;">&nbsp;</span></div>
            </td>
            <td style="width: 38%;">
                <div>Data rozpoczęcia likwidacji <span class="line">&nbsp;</span></div>
                <div style="margin-top: 14px;">Decyzję komisji zatwierdzam</div>
                <table style="margin-top: 26px; border: none;">
                    <tr>
                        <td style="border: none; width: 50%;"><span class="line" style="min-width: 90%;">&nbsp;</span><div class="cap">(data)</div></td>
                        <td style="border: none; width: 50%;"><span class="line" style="min-width: 90%;">&nbsp;</span><div class="cap">(kierownik jednostki)</div></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="height: 28px;"></div>

    {{-- Accounting part --}}
    <table>
        <tr><td class="sec">Komórka organizacyjna</td></tr>
        <tr>
            <td style="height: 44px;">
                Wpłynęło dnia <span class="line">&nbsp;</span>
                Dotyczy <span class="line" style="min-width: 320px;">{{ $assetName }} ({{ $assetNo }})</span>
                <div style="margin-top: 20px; text-align: right;"><span class="line">&nbsp;</span><div class="cap" style="text-align: right;">(podpis)</div></div>
            </td>
        </tr>
    </table>

    <div style="margin: 10px 0 6px;">
        <span class="val" style="font-size: 13px;">Polecenie księgowania nr</span>
        <span class="line">&nbsp;</span> z dnia <span class="line">&nbsp;</span>
    </div>

    <table>
        <tr>
            <th style="width: 46%;">Treść</th>
            <th style="width: 16%;">Konto Winien</th>
            <th style="width: 22%;">Kwota</th>
            <th style="width: 16%;">Konto Ma</th>
        </tr>
        @for ($i = 0; $i < 5; $i++)
            <tr style="height: 24px;"><td></td><td></td><td></td><td></td></tr>
        @endfor
        <tr>
            <td style="height: 56px;">Uwagi:</td>
            <td colspan="3">
                Zaksięgowano
                <table style="margin-top: 26px; border: none;">
                    <tr>
                        <td style="border: none; width: 50%;"><span class="line" style="min-width: 80%;">&nbsp;</span><div class="cap">(data)</div></td>
                        <td style="border: none; width: 50%;"><span class="line" style="min-width: 80%;">&nbsp;</span><div class="cap">(podpis)</div></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
