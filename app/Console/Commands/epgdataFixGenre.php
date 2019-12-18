<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class epgdataFixGenre extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'epgdata:fixgenre';

    /**
     * The console command description.
     *
     * @var string30
     */
    protected $description = 'Correcting false genre information.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // Get channel whitelist
        $channels_whitelist = json_decode(json_encode(DB::table('epgdata_channel_whitelist')->pluck('name', 'channel_id')), true);

        // Get all movies and serie
        DB::table('epgdata_movie')
            ->select('epgdata_movie.id', 'epgdata_movie.title', 'epgdata_movie.subtitle', 'epgdata_movie.genreid', 'epgdata_movie.starttime', 'epgdata_channel.name', 'epgdata_movie.tvchannel_id', 'epgdata_movie.actor')
            ->leftJoin('epgdata_channel', 'epgdata_movie.tvchannel_id', '=', 'epgdata_channel.channel_id')
            ->where('epgdata_movie.actor', '!=', '')
            ->where('epgdata_movie.category_id', '<', '223')
            ->where('epgdata_movie.tvshow_length', '>', '40')
            ->where('epgdata_channel.language', '=', 'de')
            ->whereIn('epgdata_channel.channel_id', array_keys($channels_whitelist))
            ->where(function ($query) {
                $query
                    ->whereRaw('UNIX_TIMESTAMP(epgdata_movie.starttime) > ? AND UNIX_TIMESTAMP(epgdata_movie.starttime) < ?', [strtotime(date('Y-m-d 00:00:00', strtotime('+2 days'))), strtotime(date('Y-m-d 05:00:00', strtotime('+2 days')))])
                    ->orWhereRaw('UNIX_TIMESTAMP(epgdata_movie.starttime) > ? AND UNIX_TIMESTAMP(epgdata_movie.starttime) < ?', [strtotime(date('Y-m-d 20:00:00', strtotime('+2 days'))), strtotime(date('Y-m-d 05:00:00', strtotime('+3 days')))]);
            })
            ->orderBy('epgdata_movie.id')
            ->chunk(1000, function ($movies) {
            foreach ($movies as $movie) {

                $imdbData = json_decode(file_get_contents( env('MOVIE_API_URL') . '/?t=' . urlencode($movie->title) . '&apikey=' . env('MOVIE_API_KEY')), true);

                if(
                    isset($imdbData['Genre']) && Str::contains($imdbData['Genre'], 'Horror')
                ) {
                    $actors = explode(', ', $imdbData['Actors']);
                    if(Str::contains($movie->actor, $actors[0])) {
                            DB::table('epgdata_movie')
                                ->where('id', $movie->id)
                                ->update(['imdb_category' => $imdbData['Genre']]);
                    }
                }
                DB::table('epgdata_movie')
                    ->where('id', $movie->id)
                    ->update(['fixed' => 1]);
            }

        });

    }

}