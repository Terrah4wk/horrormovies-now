<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class SiteController extends Controller
{
    public function __construct()
    {
        self::setPageMeta();
        // Please teach me if there is a better way; tried it per AppServiceProvider. But i didn't get it to implement the Route.
    }

    /**
     * Show the profile for the given user.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function imprint()
    {
        return view('page_imprint', []);
    }

    /*
     * Get meta data
     */
    public static function setPageMeta()
    {
        $name = (string) Route::currentRouteName();
        $pageMeta = Cache::remember('page_meta'.$name, 1, function () use ($name) {
            return DB::table('page_meta')
                ->select()
                ->where('page_meta.route', '=', $name)->first();
        });

        View::share('pageMeta', $pageMeta);
    }
}
