<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TvController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {

        // Get channel whitelist
        $channels_whitelist = json_decode(json_encode(Cache::remember('epgdata_channel_whitelist', 9000, function () {
            return DB::table('epgdata_channel_whitelist')->pluck('name', 'channel_id');
        })), true);

        $movies = Cache::remember('tv_index', 5, function () use ($channels_whitelist) {
            return DB::table('epgdata_movie')
                ->select('epgdata_movie.id', 'epgdata_channel.name', 'epgdata_movie.starttime', 'epgdata_movie.endtime', 'epgdata_movie.title', 'epgdata_movie.subtitle', 'epgdata_channel.name', 'epgdata_movie.comment_long', 'epgdata_movie.imdb_category', 'epgdata_movie.image_big')
                ->leftJoin('epgdata_channel', 'epgdata_movie.tvchannel_id', '=', 'epgdata_channel.channel_id')
                ->leftJoin('epgdata_genre', 'epgdata_movie.genreid', '=', 'epgdata_genre.genreid')
                ->where('epgdata_channel.language', '=', 'de')
                ->whereIn('epgdata_channel.channel_id', array_keys($channels_whitelist))
                ->whereNotNull('epgdata_movie.imdb_category')
                ->whereRaw('UNIX_TIMESTAMP(epgdata_movie.starttime) > ? AND UNIX_TIMESTAMP(epgdata_movie.starttime) < ?', [strtotime(date('Y-m-d H:i:s', strtotime('-2 hours'))), strtotime(date('Y-m-d H:i:s', strtotime('36 hours')))])
                ->orWhereIn('epgdata_movie.genreid', [116,216])
                ->whereIn('epgdata_channel.channel_id', array_keys($channels_whitelist))
                ->whereRaw('UNIX_TIMESTAMP(epgdata_movie.starttime) > ? AND UNIX_TIMESTAMP(epgdata_movie.starttime) < ?', [strtotime(date('Y-m-d H:i:s', strtotime('-2 hours'))), strtotime(date('Y-m-d H:i:s', strtotime('36 hours')))])
                ->orderBy('epgdata_movie.starttime')->get();
        });

        $active_movie = [];
        foreach ($movies AS $movie) {
            $active_movie[$movie->id] = 'inactive';
            if(strtotime($movie->starttime) < time() && strtotime($movie->endtime) > time()) {
                $active_movie[$movie->id] = 'active';
            }
        }

        return view('tv_current', ['movies' => $movies, 'active_movie' => $active_movie]);

    }

}