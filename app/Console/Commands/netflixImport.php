<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Imdb\Config;
use Imdb\Title;
use Statickidz\GoogleTranslate;
use GuzzleHttp\Client;

class netflixImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netflix:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'imports netflix data';

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

        $config = new Config();
        $config->language = 'de-DE,de,en';
        $google_translator = new GoogleTranslate();

        /*
         * Truncate table
         */
        DB::table('netflix_movie')->truncate();


        /*
         * New on netflix
         */
        $client = new Client();
        $result = $client->request('GET', env('NETFLIX_API_URL') . '?q=get%3Anew7%3ADE&p=1&t=ns&st=adv', [
            'headers' => [
                env('NETFLIX_API_HEADER_HOST_KEY') => env('NETFLIX_API_HEADER_HOST_VALUE'),
                env('NETFLIX_API_HEADER_SECRET_KEY') => env('NETFLIX_API_HEADER_SECRET_VALUE')
            ]
        ]);

        $response = $result->getBody();

        if(isset($response)) {
            $netflix_new_movies = json_decode($response, true);
            unset($response);

            foreach ($netflix_new_movies['ITEMS'] AS $netflix_new_movies) {

                if(empty($netflix_new_movies['imdbid'])) {
                    continue;
                }

                $imdb = new Title($netflix_new_movies['imdbid'], $config);
                $genre = implode(',', $imdb->genres());

                if(isset($genre) && Str::contains($genre, 'Horror')) {

                    $movie_translated = DB::table('movie_translated')->where('imdbid', $netflix_new_movies['imdbid'])->first();

                    if(!isset($movie_translated->imdbid)) {

                        $translated_synopsis = $google_translator->translate('en', 'de', $netflix_new_movies['synopsis']);
                        usleep(300);

                        $insert_data = [
                            'id' => null,
                            'imdbid' => $netflix_new_movies['imdbid'],
                            'description' => $translated_synopsis,
                            'description_en' => $netflix_new_movies['synopsis'],
                        ];

                        DB::table('movie_translated')->insert($insert_data);

                    }

                    $insert_data = [
                        'id' => null,
                        'netflixid' => $netflix_new_movies['netflixid'],
                        'title' => $netflix_new_movies['title'],
                        'image' => $netflix_new_movies['image'],
                        'released' => $netflix_new_movies['released'],
                        'runtime' => $netflix_new_movies['runtime'],
                        'release_date' => $netflix_new_movies['unogsdate'],
                        'imdbid' => $netflix_new_movies['imdbid'],
                    ];

                    DB::table('netflix_movie')->insert($insert_data);

                }

            }

        }

        /*
         * Movies on netflix
         */
        $genre_ids = explode(',', '10695,10944,1694,42023,45028,48303,61546,75405,75804,75930,8195,83059,8711,89585');

        foreach ($genre_ids AS $genre_id) {

            $client = new Client();
            $result = $client->request('GET', env('NETFLIX_API_URL') . '?q=%7Bquery%7D-!1900%2C2019-!0%2C5-!0%2C10-!' . $genre_id . '-!Any-!Any-!Any-!gt100-!%7Bdownloadable%7D&t=ns&cl=39&st=adv&ob=Relevance&p=1&sa=and', [
                'headers' => [
                    env('NETFLIX_API_HEADER_HOST_KEY') => env('NETFLIX_API_HEADER_HOST_VALUE'),
                    env('NETFLIX_API_HEADER_SECRET_KEY') => env('NETFLIX_API_HEADER_SECRET_VALUE')
                ]
            ]);

            $response = $result->getBody();

            if(isset($response)) {

                $netflix_movie = json_decode($response, true);
                unset($response);

                foreach ($netflix_movie['ITEMS'] AS $netflix_movie) {

                    if(empty($netflix_movie['imdbid'])) {
                        continue;
                    }

                    $imdb = new Title($netflix_movie['imdbid'], $config);
                    $genre = implode(',', $imdb->genres());

                    if(isset($genre) && Str::contains($genre, 'Horror')) {

                        $movie_translated = DB::table('movie_translated')->where('imdbid', $netflix_movie['imdbid'])->first();

                        if(!isset($movie_translated->imdbid)) {

                            $translated_synopsis = $google_translator->translate('en', 'de', $netflix_movie['synopsis']);
                            usleep(300);

                            $insert_data = [
                                'id' => null,
                                'imdbid' => $netflix_movie['imdbid'],
                                'description' => $translated_synopsis,
                                'description_en' => $netflix_movie['synopsis']
                            ];

                            DB::table('movie_translated')->insert($insert_data);

                        }

                        $translated_synopsis = $google_translator->translate('en', 'de', $netflix_movie['synopsis']);
                        usleep(250);

                        if(!empty($translated_synopsis)) {
                            $netflix_movie['synopsis'] = $translated_synopsis; // just overwrite
                        }

                        $insert_data = [
                            'id' => null,
                            'netflixid' => $netflix_movie['netflixid'],
                            'title' => $netflix_movie['title'],
                            'image' => $netflix_movie['image'],
                            'released' => $netflix_movie['released'],
                            'runtime' => $netflix_movie['runtime'],
                            'date' => $netflix_movie['unogsdate'],
                            'imdbid' => $netflix_movie['imdbid'],
                            'type' => $imdb->movietype(),
                        ];

                        DB::table('netflix_movie')->insert($insert_data);

                    }

                }

            }

        }

        /*
         * Expiring netflix movies
         */
        $client = new Client();
        $result = $client->request('GET', env('NETFLIX_API_URL') . '?q=get%3Aexp%3ADE&t=ns&st=adv&p=1', [
            'headers' => [
                env('NETFLIX_API_HEADER_HOST_KEY') => env('NETFLIX_API_HEADER_HOST_VALUE'),
                env('NETFLIX_API_HEADER_SECRET_KEY') => env('NETFLIX_API_HEADER_SECRET_VALUE')
            ]
        ]);

        $response = $result->getBody();

        if(isset($response)) {
            $netflix_expired_movies = json_decode($response, true);
            unset($response);

            foreach ($netflix_expired_movies['ITEMS'] AS $netflix_expired_movie) {

                if(empty($netflix_expired_movie['imdbid'])) {
                    continue;
                }

                $imdb = new Title($netflix_expired_movie['imdbid'], $config);
                $genre = implode(',', $imdb->genres());

                if(isset($genre) && Str::contains($genre, 'Horror')) {

                    $movie_translated = DB::table('movie_translated')->where('imdbid', $netflix_expired_movie['imdbid'])->first();

                    if(!isset($movie_translated->imdbid)) {

                        $translated_synopsis = $google_translator->translate('en', 'de', $netflix_expired_movie['synopsis']);
                        usleep(300);

                        $insert_data = [
                            'id' => null,
                            'imdbid' => $netflix_expired_movie['imdbid'],
                            'description' => $translated_synopsis,
                            'description_en' => $netflix_expired_movie['synopsis']
                        ];

                        DB::table('movie_translated')->insert($insert_data);

                    }

                    $insert_data = [
                        'id' => null,
                        'netflixid' => $netflix_expired_movie['netflixid'],
                        'title' => $netflix_expired_movie['title'],
                        'image' => $netflix_expired_movie['image'],
                        'released' => $netflix_expired_movie['released'],
                        'runtime' => $netflix_expired_movie['runtime'],
                        'expire_date' => $netflix_expired_movie['unogsdate'],
                        'imdbid' => $netflix_expired_movie['imdbid'],
                        'type' => $imdb->movietype(),
                    ];

                    DB::table('netflix_movie')->insert($insert_data);

                }

            }

        }

    }

}