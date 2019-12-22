<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
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
        $client = new Client();
        $result = $client->request('GET', env('NETFLIX_API_URL').'?q=get%3Anew7%3ADE&p=1&t=ns&st=adv', [
            'headers' => [
                env('NETFLIX_API_HEADER_HOST_KEY')   => env('NETFLIX_API_HEADER_HOST_VALUE'),
                env('NETFLIX_API_HEADER_SECRET_KEY') => env('NETFLIX_API_HEADER_SECRET_VALUE'),
            ],
        ]);

        $response = $result->getBody();

        if (isset($response)) {
            $netflix_new_movies = json_decode($response, true);
            unset($response);

            foreach ($netflix_new_movies['ITEMS'] as $netflix_new_movies) {
                if (empty($netflix_new_movies['imdbid'])) {
                    continue;
                }

                try {
                    $imdb = new Title($netflix_new_movies['imdbid'], $config);
                    $genre = implode(',', $imdb->genres());
                } catch (\Imdb\Exception\Http $e) {
                    continue;
                }

                if (isset($genre) && Str::contains($genre, 'Horror')) {
                    $netflix_movie_translation = DB::table('netflix_movie_translation')->where('imdbid', $netflix_new_movies['imdbid'])->first();

                    if (! isset($netflix_movie_translation->imdbid)) {
                        $translated_synopsis = $google_translator->translate('en', 'de', $netflix_new_movies['synopsis']);
                        sleep(2);

                        if (! empty($translated_synopsis)) {
                            $netflix_new_movies['synopsis'] = $netflix_movie_translation->description; // just overwrite
                        }

                        $insert_data = [
                            'id'             => null,
                            'imdbid'         => $netflix_new_movies['imdbid'],
                            'description'    => $translated_synopsis,
                            'description_en' => $netflix_new_movies['synopsis'],
                        ];

                        DB::table('netflix_movie_translation')->insert($insert_data);
                    }

                    $insert_data = [
                        'id'           => null,
                        'netflixid'    => $netflix_new_movies['netflixid'],
                        'title'       => html_entity_decode($netflix_new_movies['title'], ENT_QUOTES, 'UTF-8'),
                        'image'       => proximage($netflix_new_movies['image'])->width(474)->get(),
                        'released'    => $netflix_new_movies['released'],
                        'runtime'     => $netflix_new_movies['runtime'],
                        'release_date' => $netflix_new_movies['unogsdate'],
                        'imdbid'      => $netflix_new_movies['imdbid'],
                        'rating'      => round($imdb->rating()),
                        'type'        => $imdb->movietype(),
                    ];

                    DB::table('netflix_movie')->insert($insert_data);
                }
            }
        }

        /*
         * Movies on netflix
         */
        $genre_ids = explode(',', '10695,10944,1694,42023,45028,48303,61546,75405,75804,75930,8195,83059,8711,89585');

        foreach ($genre_ids as $genre_id) {
            $client = new Client();
            $result = $client->request('GET', env('NETFLIX_API_URL').'?q=%7Bquery%7D-!1900%2C2019-!0%2C5-!0%2C10-!'.$genre_id.'-!Any-!Any-!Any-!gt100-!%7Bdownloadable%7D&t=ns&cl=39&st=adv&ob=Relevance&p=1&sa=and', [
                'headers' => [
                    env('NETFLIX_API_HEADER_HOST_KEY')   => env('NETFLIX_API_HEADER_HOST_VALUE'),
                    env('NETFLIX_API_HEADER_SECRET_KEY') => env('NETFLIX_API_HEADER_SECRET_VALUE'),
                ],
            ]);

            $response = $result->getBody();

            if (isset($response)) {
                $netflix_movie = json_decode($response, true);
                unset($response);

                foreach ($netflix_movie['ITEMS'] as $netflix_movie) {
                    if (empty($netflix_movie['imdbid'])) {
                        continue;
                    }

                    try {
                        $imdb = new Title($netflix_movie['imdbid'], $config);
                        $genre = implode(',', $imdb->genres());
                    } catch (\Imdb\Exception\Http $e) {
                        continue;
                    }

                    if (isset($genre) && Str::contains($genre, 'Horror')) {
                        $netflix_movie_translation = DB::table('netflix_movie_translation')->where('imdbid', $netflix_movie['imdbid'])->first();

                        if (! isset($netflix_movie_translation->imdbid)) {
                            $translated_synopsis = $google_translator->translate('en', 'de', $netflix_movie['synopsis']);
                            sleep(2);

                            $insert_data = [
                                'id'             => null,
                                'imdbid'         => $netflix_movie['imdbid'],
                                'description'    => $translated_synopsis,
                                'description_en' => $netflix_movie['synopsis'],
                            ];

                            DB::table('netflix_movie_translation')->insert($insert_data);
                        }

                        if (! empty($translated_synopsis)) {
                            $netflix_movie['synopsis'] = $netflix_movie_translation->description; // just overwrite
                        }

                        $insert_data = [
                            'id'        => null,
                            'netflixid' => $netflix_movie['netflixid'],
                            'title'       => html_entity_decode($netflix_movie['title'], ENT_QUOTES, 'UTF-8'),
                            'image'       => proximage($netflix_movie['image'])->width(474)->get(),
                            'released'    => $netflix_movie['released'],
                            'runtime'     => $netflix_movie['runtime'],
                            'date'        => $netflix_movie['unogsdate'],
                            'imdbid'      => $netflix_movie['imdbid'],
                            'rating'      => round($imdb->rating()),
                            'type'        => $imdb->movietype(),
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
        $result = $client->request('GET', env('NETFLIX_API_URL').'?q=get%3Aexp%3ADE&t=ns&st=adv&p=1', [
            'headers' => [
                env('NETFLIX_API_HEADER_HOST_KEY')   => env('NETFLIX_API_HEADER_HOST_VALUE'),
                env('NETFLIX_API_HEADER_SECRET_KEY') => env('NETFLIX_API_HEADER_SECRET_VALUE'),
            ],
        ]);

        $response = $result->getBody();

        if (isset($response)) {
            $netflix_expired_movies = json_decode($response, true);
            unset($response);

            foreach ($netflix_expired_movies['ITEMS'] as $netflix_expired_movie) {
                if (empty($netflix_expired_movie['imdbid'])) {
                    continue;
                }

                try {
                    $imdb = new Title($netflix_expired_movie['imdbid'], $config);
                    $genre = implode(',', $imdb->genres());
                } catch (\Imdb\Exception\Http $e) {
                    continue;
                }


                if (isset($genre) && Str::contains($genre, 'Horror')) {
                    $netflix_movie_translation = DB::table('netflix_movie_translation')->where('imdbid', $netflix_expired_movie['imdbid'])->first();

                    if (! isset($netflix_movie_translation->imdbid)) {
                        $translated_synopsis = $google_translator->translate('en', 'de', $netflix_expired_movie['synopsis']);
                        sleep(2);

                        if (! empty($translated_synopsis)) {
                            $netflix_expired_movie['synopsis'] = $netflix_movie_translation->description; // just overwrite
                        }

                        $insert_data = [
                            'id'             => null,
                            'imdbid'         => $netflix_expired_movie['imdbid'],
                            'description'    => $translated_synopsis,
                            'description_en' => $netflix_expired_movie['synopsis'],
                        ];

                        DB::table('netflix_movie_translation')->insert($insert_data);
                    }

                    $insert_data = [
                        'id'          => null,
                        'netflixid'   => $netflix_expired_movie['netflixid'],
                        'title'       => html_entity_decode($netflix_expired_movie['title'], ENT_QUOTES, 'UTF-8'),
                        'image'       => proximage($netflix_expired_movie['image'])->width(474)->get(),
                        'released'    => $netflix_expired_movie['released'],
                        'runtime'     => $netflix_expired_movie['runtime'],
                        'expire_date' => $netflix_expired_movie['unogsdate'],
                        'imdbid'      => $netflix_expired_movie['imdbid'],
                        'rating'      => round($imdb->rating()),
                        'type'        => $imdb->movietype(),
                    ];

                    DB::table('netflix_movie')->insert($insert_data);
                }
            }
        }
    }
}
