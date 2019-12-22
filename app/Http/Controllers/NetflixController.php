<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class NetflixController extends Controller
{
    public function __construct()
    {
        self::setPageMeta();
        // Please teach me if there is a better way; tried it per AppServiceProvider. But i didn't get it to implement the Route.
    }

    /**
     * Show new horror movies.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        $movies = Cache::remember('netflix_new', 240, function () {
            return DB::table('netflix_movie')
                ->select(['netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'netflix_movie_translation.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.release_date', 'netflix_movie_translation.genre', 'netflix_movie.type', 'netflix_movie.rating', 'netflix_movie.imdbid', 'netflix_movie.netflixid' ])
                ->leftJoin('netflix_movie_translation', 'netflix_movie.imdbid', '=', 'netflix_movie_translation.imdbid')
                ->whereNotNull('netflix_movie.release_date')
                ->groupBy('netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'netflix_movie_translation.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.release_date', 'netflix_movie_translation.genre', 'netflix_movie.type', 'netflix_movie.rating', 'netflix_movie.imdbid', 'netflix_movie.netflixid')
                ->orderBy('netflix_movie.release_date')
                ->orderBy('netflix_movie.rating')
                ->get()->toArray();
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
        $movies = Cache::remember('netflix_current', 240, function () {
            return DB::table('netflix_movie')
                ->select(['netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'netflix_movie_translation.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.date', 'netflix_movie_translation.genre', 'netflix_movie.type', 'netflix_movie.rating', 'netflix_movie.imdbid', 'netflix_movie.imdbid' ])
                ->leftJoin('netflix_movie_translation', 'netflix_movie.imdbid', '=', 'netflix_movie_translation.imdbid')
                ->whereNotNull('netflix_movie.date')
                ->groupBy('netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'netflix_movie_translation.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.date', 'netflix_movie_translation.genre', 'netflix_movie.type', 'netflix_movie.rating', 'netflix_movie.imdbid', 'netflix_movie.imdbid')
                ->orderBy('netflix_movie.rating', 'desc')->get()->toArray();
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
        $movies = Cache::remember('netflix_expire', 240, function () {
            return DB::table('netflix_movie')
                ->select(['netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'netflix_movie_translation.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.expire_date', 'netflix_movie_translation.genre', 'netflix_movie.type', 'netflix_movie.rating', 'netflix_movie.imdbid', 'netflix_movie.imdbid' ])
                ->leftJoin('netflix_movie_translation', 'netflix_movie.imdbid', '=', 'netflix_movie_translation.imdbid')
                ->whereNotNull('netflix_movie.expire_date')
                ->groupBy('netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'netflix_movie_translation.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.expire_date', 'netflix_movie_translation.genre', 'netflix_movie.type', 'netflix_movie.rating', 'netflix_movie.imdbid', 'netflix_movie.imdbid')
                ->orderBy('netflix_movie.expire_date')
                ->orderBy('netflix_movie.rating')
                ->get()->toArray();
        });

        return view('netflix_expire', ['movies' => $movies]);
    }

    /*
     * Get meta data
     */
    public static function setPageMeta()
    {
        $name = (string) Route::currentRouteName();
        $pageMeta = Cache::rememberForever('page_meta'.$name, function () use ($name) {
            return DB::table('page_meta')
                ->select()
                ->where('page_meta.route', '=', $name)->first();
        });

        View::share('pageMeta', $pageMeta);
    }
}
