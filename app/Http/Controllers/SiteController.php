<?php

namespace App\Http\Controllers;

class SiteController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function imprint()
    {
        return view('imprint', []);
    }
}
