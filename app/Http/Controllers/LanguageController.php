<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch($locale, Request $request)
    {
        if (! in_array($locale, ['en', 'id'])) {
            abort(400);
        }

        Session::put('locale', $locale);

        return redirect()->back();
    }
}
