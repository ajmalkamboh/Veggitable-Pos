<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Switch application locale.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['en', 'ur'])) {
            Session::put('locale', $locale);
        }

        return redirect()->back();
    }
}
