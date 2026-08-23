<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransferType;
use App\Models\TransferRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class DrukController extends Controller
{
    /** Zmiana Miejsca Użytkowania — the transfer form. */
    public function zmu(TransferRequest $transferRequest): Response
    {
        Gate::authorize('decide', $transferRequest);
        abort_unless($transferRequest->type === TransferType::Transfer, 404);

        $transferRequest->load(['asset', 'sourceField', 'targetField', 'requester']);

        return Pdf::loadView('pdf.zmu', ['request' => $transferRequest])
            ->setPaper('a4', 'landscape')
            ->download("ZMU-{$transferRequest->id}.pdf");
    }

    /** Protokół likwidacji — the liquidation form. */
    public function likwidacja(TransferRequest $transferRequest): Response
    {
        Gate::authorize('decide', $transferRequest);
        abort_unless($transferRequest->type === TransferType::Liquidation, 404);

        $transferRequest->load(['asset', 'sourceField', 'requester']);

        return Pdf::loadView('pdf.likwidacja', ['request' => $transferRequest])
            ->download("Likwidacja-{$transferRequest->id}.pdf");
    }
}
