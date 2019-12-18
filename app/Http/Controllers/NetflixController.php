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
                ->select(['netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'movie_translated.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.release_date', 'movie_translated.genre', 'netflix_movie.type', ])
                ->leftJoin('movie_translated', 'netflix_movie.imdbid', '=', 'movie_translated.imdbid')
                ->whereNotNull('netflix_movie.release_date')
                ->groupBy('netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'movie_translated.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.release_date', 'movie_translated.genre', 'netflix_movie.type')
                ->orderBy('netflix_movie.release_date')->get()->toArray();
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
                ->select(['netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'movie_translated.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.date', 'movie_translated.genre', 'netflix_movie.type', ])
                ->leftJoin('movie_translated', 'netflix_movie.imdbid', '=', 'movie_translated.imdbid')
                ->whereNotNull('netflix_movie.date')
                ->groupBy('netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'movie_translated.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.date', 'movie_translated.genre', 'netflix_movie.type')
                ->orderBy('netflix_movie.date')->get()->toArray();
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
                ->select(['netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'movie_translated.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.expire_date', 'movie_translated.genre', 'netflix_movie.type', ])
                ->leftJoin('movie_translated', 'netflix_movie.imdbid', '=', 'movie_translated.imdbid')
                ->whereNotNull('netflix_movie.expire_date')
                ->groupBy('netflix_movie.netflixid', 'netflix_movie.title', 'netflix_movie.image', 'movie_translated.description', 'netflix_movie.released', 'netflix_movie.runtime',
                    'netflix_movie.expire_date', 'movie_translated.genre', 'netflix_movie.type')
                ->orderBy('netflix_movie.expire_date')->get()->toArray();
        });

        return view('netflix_expire', ['movies' => $movies]);
    }
}
