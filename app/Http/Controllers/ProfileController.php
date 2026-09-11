<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Http\Requests\ProfileRequest;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'stats' => [
                'companies' => $user->companies()->count(),
                'contacts' => $user->contacts()->count(),
                'openDeals' => $user->deals()->whereNotIn('stage', ['won', 'lost'])->count(),
                'quotes' => $user->quotes()->count(),
                'openTasks' => Activity::query()->ownedBy($user->id)->tasks()->whereNull('completed_at')->count(),
                'wonValue' => (float) $user->deals()->where('stage', 'won')->sum('value'),
            ],
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Profil je ažuriran.');
    }

    public function updatePassword(PasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated()['password']]);

        return back()->with('success', 'Lozinka je promijenjena.');
    }
}
