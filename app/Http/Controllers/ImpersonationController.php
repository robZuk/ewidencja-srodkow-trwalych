<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Session takeover ("przejęcie sesji"). The original admin's id is stashed in the
 * session so it can be restored when impersonation ends.
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('impersonate', $user);

        $request->session()->put('impersonator_id', Auth::id());
        Auth::login($user);

        return redirect()->route('assets.index');
    }

    public function stop(Request $request): RedirectResponse
    {
        $originalId = $request->session()->pull('impersonator_id');
        abort_if($originalId === null, 403, 'Nie trwa żadne przejęcie sesji.');

        Auth::loginUsingId($originalId);

        return redirect()->route('users.index');
    }
}
