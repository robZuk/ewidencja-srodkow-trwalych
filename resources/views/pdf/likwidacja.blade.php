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
        .title { font-size: 16px; font-weight: bold; letter-spacing: 6px; }
        .lbl { font-size: 8px; color: #333; }
        .center { text-align: center; }
        .right { text-align: right; }
        .cb { display: inline-block; width: 10px; height: 10px; border: 1px solid #000; text-align: center; line-height: 10px; font-size: 8px; margin: 0 2px; }
        .fill { border-bottom: 1px dotted #555; }
        .sec { background: #eee; font-weight: bold; text-align: center; }
        .tall { height: 60px; }
        .gap { height: 26px; border: none; }
    </style>
</head>
<body>
    @php($assetName = $request->asset?->name ?? $request->asset_snapshot['name'] ?? '—')
    @php($assetNo = $request->asset?->inventory_number ?? $request->asset_snapshot['inventory_number'] ?? '—')
    @php($qty = (int) ($request->asset?->quantity ?? 1))

    {{-- LT form --}}
    <table>
        <tr>
            <td style="width:62%;">
                <div class="title">L I K W I D A C J A</div>
                <div style="margin-top:6px;">
                    Środka trwałego <span class="cb">X</span> LT &nbsp;&nbsp;
                    <span style="font-size:14px; font-weight:bold;">NR</span>
                    <span class="fill">&nbsp;{{ $request->zmu_number ?? $request->id }}&nbsp;</span>
                </div>
                <div>Przedmiotu nietrwałego <span class="cb">&nbsp;</span> LN</div>
            </td>
            <td style="width:38%;">
                Komórka organizacyjna
                <div style="margin-top:6px;">Symbol kosztów</div>
            </td>
        </tr>
        <tr>
            <th style="width:62%;" class="center">Nazwa środka trwałego – przedmiotu nietrwałego</th>
            <th style="width:38%;" class="center">Nr(y) inwentarzowe(y)</th>
        </tr>
        <tr>
            <td class="tall">
                {{ $assetName }}
                <div style="margin-top:26px;">Ilość sztuk <span class="fill">&nbsp;{{ $qty }}&nbsp;</span></div>
            </td>
            <td class="tall">{{ $assetNo }}</td>
        </tr>
        <tr>
            <td colspan="2">
                Orzeczenie Komisji Likwidacyjnej
                <div style="min-height:44px;">{{ $request->note }}</div>
            </td>
        </tr>
        <tr>
            <td style="width:62%;">
                <div class="center">Komisja likwidacyjna</div>
                <div class="center lbl">(podpisy)</div>
                <div style="margin-top:6px;">Data: <span class="fill">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                <div style="margin-top:18px;" class="fill">&nbsp;</div>
            </td>
            <td style="width:38%;">
                Data rozpoczęcia likwidacji <span class="fill">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                <div style="margin-top:12px;">Decyzję komisji zatwierdzam</div>
                <div style="margin-top:20px;">
                    <span class="fill">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> &nbsp; <span class="fill">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </div>
                <div class="lbl"><span style="display:inline-block;width:45%;text-align:center;">(data)</span><span style="display:inline-block;width:50%;text-align:center;">(kierownik jednostki)</span></div>
            </td>
        </tr>
    </table>

    <div style="height:24px;"></div>

    {{-- Accounting part --}}
    <table>
        <tr><td class="sec">Komórka organizacyjna</td></tr>
        <tr>
            <td>
                Wpłynęło dnia <span class="fill">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                Dotyczy <span class="fill">&nbsp;{{ $assetName }} ({{ $assetNo }})&nbsp;</span>
                <div style="margin-top:22px;" class="right lbl">(podpis)</div>
            </td>
        </tr>
    </table>

    <table style="border-top:none;">
        <tr>
            <td style="border:none; padding-top:8px;">
                <strong style="font-size:12px;">Polecenie księgowania nr</strong>
                <span class="fill">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> z dnia <span class="fill">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <th style="width:46%;" class="center">Treść</th>
            <th style="width:16%;" class="center">Konto Winien</th>
            <th style="width:22%;" class="center">Kwota</th>
            <th style="width:16%;" class="center">Konto Ma</th>
        </tr>
        @for ($i = 0; $i < 5; $i++)
            <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
        @endfor
        <tr>
            <td style="width:46%;">Uwagi:</td>
            <td colspan="3">
                Zaksięgowano
                <div style="margin-top:20px;" class="lbl">
                    <span style="display:inline-block;width:45%;text-align:center;">(data)</span>
                    <span style="display:inline-block;width:45%;text-align:center;">(podpis)</span>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
