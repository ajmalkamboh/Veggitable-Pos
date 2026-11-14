<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Switch application locale and persist preference.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['en', 'ur'], true)) {
            Session::put('locale', $locale);

            if (Auth::check()) {
                $user = Auth::user();
                $preferences = $user->preferences ?? [];
                $preferences['locale'] = $locale;
                $user->update(['preferences' => $preferences]);
            }
        }

        return redirect()->back();
    }
}
