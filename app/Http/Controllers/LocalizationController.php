<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;

class LocalizationController extends Controller
{
    /**
     * Switch the application language.
     *
     * @param string $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switchLanguage($locale)
    {
        if (in_array($locale, ['en', 'id'])) {
            Session::put('locale', $locale);
            App::setLocale($locale);
        }

        return redirect()->back();
    }
}
