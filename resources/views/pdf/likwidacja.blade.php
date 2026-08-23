<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>@page { size: a4 portrait; margin: 32px; }</style>
    @include('pdf.partials.styles')
</head>
<body>
    @php($assetName = $request->asset?->name ?? $request->asset_snapshot['name'] ?? '—')
    @php($assetNo = $request->asset?->inventory_number ?? $request->asset_snapshot['inventory_number'] ?? '—')
    @php($qty = (int) ($request->asset?->quantity ?? 1))

    {{-- Header --}}
    <table>
        <tr>
            <td class="top stamp" style="width: 22%;">
                <div class="stamp-cap">(pieczęć jednostki)</div>
            </td>
            <td class="mid" style="width: 48%;">
                <div class="title">L I K W I D A C J A</div>
                <div style="margin-top: 8px;">
                    Środka trwałego <span class="cb">X</span> LT
                    &nbsp;&nbsp;<span class="title" style="letter-spacing: 1px;">NR</span>
                    <span class="line val">{{ $request->zmu_number ?? $request->id }}</span>
                </div>
                <div style="margin-top: 5px;">Przedmiotu nietrwałego <span class="cb">&nbsp;</span> LN</div>
            </td>
            <td class="mid" style="width: 30%;">
                <div>Komórka organizacyjna</div>
                <div style="margin-top: 16px;">Symbol kosztów</div>
            </td>
        </tr>
    </table>

    {{-- Subject --}}
    <table style="border-top: none;">
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
            <th class="mid center" style="width: 46%;">Treść</th>
            <th class="mid center" style="width: 16%;">Konto Winien</th>
            <th class="mid center" style="width: 22%;">Kwota</th>
            <th class="mid center" style="width: 16%;">Konto Ma</th>
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
