<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NetflixController extends Controller
{
    /**
     * Show new horror movies.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {

        $movies = Cache::remember('netflix_new', 300, function () {
            return DB::table('netflix_movie')
                ->select(['netflixid', 'title', 'image', 'description', 'released', 'runtime', 'release_date', 'genre', 'type'])
                ->whereNotNull('release_date')
                ->groupBy('netflixid', 'title', 'image', 'description', 'released', 'runtime', 'release_date', 'genre', 'type')
                ->orderBy('release_date')->get()->toArray();
        });

        return view('netflix_new', ['movies' => $movies]);

    }

    /**
     * Show horror movies currently on netflix.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function current()
    {

        $movies = Cache::remember('netflix_current', 300, function () {
            return DB::table('netflix_movie')
                ->select(['netflixid', 'title', 'image', 'description', 'released', 'runtime', 'date', 'genre', 'type'])
                ->whereNotNull('date')
                ->groupBy('netflixid', 'title', 'image', 'description', 'released', 'runtime', 'date', 'genre', 'type')
                ->orderBy('date')->get()->toArray();
        });

        return view('netflix_current', ['movies' => $movies]);

    }

    /**
     * Show horror movies that expires on netflix.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function expire()
    {

        $movies = Cache::remember('netflix_expire', 300, function () {
            return DB::table('netflix_movie')
                ->select(['netflixid', 'title', 'image', 'description', 'released', 'runtime', 'expire_date', 'genre', 'type'])
                ->whereNotNull('expire_date')
                ->groupBy('netflixid', 'title', 'image', 'description', 'released', 'runtime', 'expire_date', 'genre', 'type')
                ->orderBy('expire_date')->get()->toArray();
        });

        return view('netflix_expire', ['movies' => $movies]);

    }

}