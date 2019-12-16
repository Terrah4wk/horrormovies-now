<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Imdb\Config;
use Imdb\Title;
use Statickidz\GoogleTranslate;

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
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env('NETFLIX_API_URL') . '?q=get%3Anew7%3ADE&p=1&t=ns&st=adv',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                env('NETFLIX_API_HEADER_HOST'),
                env('NETFLIX_API_HEADER_KEY')
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        if(isset($response)) {
            $netflix_new_movies = json_decode($response, true);
            unset($response);

            foreach ($netflix_new_movies['ITEMS'] AS $netflix_new_movies) {

                if(empty($netflix_movie['imdbid'])) {
                    continue;
                }

                $imdb = new Title($netflix_new_movies['imdbid'], $config);
                $genre = implode(',', $imdb->genres());

                if(isset($genre) && Str::contains($genre, 'Horror')) {

                    $translated_synopsis = $google_translator->translate('en', 'de', $netflix_new_movies['synopsis']);
                    usleep(200);
                    if(!empty($translated_synopsis)) {
                        $netflix_new_movies['synopsis'] = $translated_synopsis; // just overwrite
                    }

                    $insert_data = [
                        'id' => null,
                        'netflixid' => $netflix_new_movies['netflixid'],
                        'title' => $netflix_new_movies['title'],
                        'image' => $netflix_new_movies['image'],
                        'description' => $netflix_new_movies['synopsis'],
                        'released' => $netflix_new_movies['released'],
                        'runtime' => $netflix_new_movies['runtime'],
                        'release_date' => $netflix_new_movies['unogsdate'],
                        'imdbid' => $netflix_new_movies['imdbid'],
                        'genre' => $genre,
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

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => env('NETFLIX_API_URL') . '?q=%7Bquery%7D-!1900%2C2019-!0%2C5-!0%2C10-!' . $genre_id . '-!Any-!Any-!Any-!gt100-!%7Bdownloadable%7D&t=ns&cl=39&st=adv&ob=Relevance&p=1&sa=and',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    env('NETFLIX_API_HEADER_HOST'),
                    env('NETFLIX_API_HEADER_KEY')
                ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);

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
                            'description' => $netflix_movie['synopsis'],
                            'released' => $netflix_movie['released'],
                            'runtime' => $netflix_movie['runtime'],
                            'date' => $netflix_movie['unogsdate'],
                            'imdbid' => $netflix_movie['imdbid'],
                            'genre' => $genre,
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
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => env('NETFLIX_API_URL') . '?q=get%3Aexp%3ADE&t=ns&st=adv&p=1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                env('NETFLIX_API_HEADER_HOST'),
                env('NETFLIX_API_HEADER_KEY')
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

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

                    $translated_synopsis = $google_translator->translate('en', 'de', $netflix_expired_movie['synopsis']);
                    usleep(250);

                    if(!empty($translated_synopsis)) {
                        $netflix_expired_movie['synopsis'] = $translated_synopsis; // just overwrite
                    }

                    $insert_data = [
                        'id' => null,
                        'netflixid' => $netflix_expired_movie['netflixid'],
                        'title' => $netflix_expired_movie['title'],
                        'image' => $netflix_expired_movie['image'],
                        'description' => $netflix_expired_movie['synopsis'],
                        'released' => $netflix_expired_movie['released'],
                        'runtime' => $netflix_expired_movie['runtime'],
                        'expire_date' => $netflix_expired_movie['unogsdate'],
                        'imdbid' => $netflix_expired_movie['imdbid'],
                        'genre' => $genre,
                        'type' => $imdb->movietype(),
                    ];

                    DB::table('netflix_movie')->insert($insert_data);

                }

            }

        }

    }

}