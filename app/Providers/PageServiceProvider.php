<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //!TODO Get route name
        $name = 'tv.index';
        var_dump($this->app->request->get);
        $pageMeta = Cache::remember('page_meta'.$name, 1, function () use ($name) {
            return DB::table('page_meta')
                ->select()
                ->where('page_meta.route', '=', $name)->first();
        });
        View::share('pageMeta', $pageMeta);
    }
}
