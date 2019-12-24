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
                env('NETFLIX_API_HEADER_HOST_KEY') => env('NETFLIX_API_HEADER_HOST_VALUE'),
                env('NETFLIX_API_HEADER_SECRET_KEY') => env('NETFLIX_API_HEADER_SECRET_VALUE'),
            ],
        ]);

        $response = $result->getBody();

        if (isset($response)) {
            $netflix_new_movies = json_decode($response, true);
            unset($response);

            foreach ($netflix_new_movies['ITEMS'] as $netflix_new_movie) {
                if (!isset($netflix_new_movie['imdbid']) ||
                    empty($netflix_new_movie['imdbid']) ||
                    !isset($netflix_new_movie['netflixid']) ||
                    empty($netflix_new_movie['netflixid'])) {
                    continue;
                }

                usleep(10);

                $netflix_url = env('NETFLIX_TITLE_URL') . $netflix_new_movie['netflixid'];
                $client = new Client();
                $response = $client->request('GET', $netflix_url);
                $html = $response->getBody()->getContents();

                /*
                 * Workaround: For API problems. I assume that movies in Germany must have at least one German subtitle or German audio.
                 * That should minimize the issues; that's not works atm for series.
                 */
                $series = false;
                preg_match_all('/<h2 class="section-header-text section-item" data-uia="section-header-text">(.*?)<\/h2>/s', $html,
                    $matches);
                if (isset($matches[1]) && is_array($matches[1])) {

                    // Find something german
                    foreach ($matches[1] AS $match) {

                        if (Str::contains($match, 'Episodes') === true) {
                            $series = true;
                        }

                    }

                }

                $german_audio = false;
                preg_match_all('/<span class="more-details-item item-audio" data-uia="more-details-item-audio">(.*?)<\/span>/s', $html,
                    $matches);
                if (isset($matches[1]) && is_array($matches[1])) {

                    // Find something german
                    foreach ($matches[1] AS $match) {

                        if (Str::contains($match, 'German') === true) {
                            $german_audio = true;
                        }

                    }

                }

                $german_subtitle = false;
                preg_match_all('/<span class="more-details-item item-subtitle" data-uia="more-details-item-subtitle">(.*?)<\/span>/s',
                    $html,
                    $matches);
                if (isset($matches[1]) && is_array($matches[1])) {

                    // Find something german
                    foreach ($matches[1] AS $match) {

                        if (Str::contains($match, 'German') === true) {
                            $german_subtitle = true;
                        }

                    }

                }

                if ($german_audio === true || $german_subtitle === true || $series === true) {

                    try {
                        $imdb = new Title($netflix_new_movie['imdbid'], $config);
                        $genre_en = implode(',', $imdb->genres());
                    } catch (\Imdb\Exception\Http $e) {
                        continue;
                    }

                    if (isset($genre_en) && Str::contains($genre_en, 'Horror')) {
                        $netflix_movie_translation = DB::table('netflix_movie_translation')->where('imdbid',
                            $netflix_new_movie['imdbid'])->first();

                        if (!isset($netflix_movie_translation->imdbid)) {
                            $translated_synopsis = $google_translator->translate('en', 'de', $netflix_new_movie['synopsis']);
                            sleep(3);
                            $translated_genre = $google_translator->translate('en', 'de', $genre_en);

                            $insert_data = [
                                'id' => null,
                                'imdbid' => $netflix_new_movie['imdbid'],
                                'genre_en' => $genre_en,
                                'genre' => $translated_genre,
                                'description' => $translated_synopsis,
                                'description_en' => $netflix_new_movie['synopsis'],
                            ];

                            DB::table('netflix_movie_translation')->insert($insert_data);
                        }

                        $insert_data = [
                            'id' => null,
                            'netflixid' => $netflix_new_movie['netflixid'],
                            'title' => html_entity_decode($netflix_new_movie['title'], ENT_QUOTES, 'UTF-8'),
                            'image' => proximage($netflix_new_movie['image'])->width(474)->get(),
                            'released' => $netflix_new_movie['released'],
                            'runtime' => $netflix_new_movie['runtime'],
                            'release_date' => $netflix_new_movie['unogsdate'],
                            'imdbid' => $netflix_new_movie['imdbid'],
                            'rating' => round($imdb->rating()),
                            'type' => $imdb->movietype(),
                        ];

                        DB::table('netflix_movie')->insert($insert_data);
                    }

                }

            }
        }

        /*
         * Movies on netflix
         */
        $genre_ids = explode(',', '10695,10944,1694,42023,45028,48303,61546,75405,75804,75930,8195,83059,8711,89585');

        foreach ($genre_ids as $genre_id) {
            $client = new Client();
            $result = $client->request('GET',
                env('NETFLIX_API_URL').'?q=%7Bquery%7D-!1900%2C2019-!0%2C5-!0%2C10-!'.$genre_id.'-!Any-!Any-!Any-!gt100-!%7Bdownloadable%7D&t=ns&cl=39&st=adv&ob=Relevance&p=1&sa=and',
                [
                    'headers' => [
                        env('NETFLIX_API_HEADER_HOST_KEY') => env('NETFLIX_API_HEADER_HOST_VALUE'),
                        env('NETFLIX_API_HEADER_SECRET_KEY') => env('NETFLIX_API_HEADER_SECRET_VALUE'),
                    ],
                ]);

            $response = $result->getBody();

            if (isset($response)) {
                $netflix_movie = json_decode($response, true);
                unset($response);

                foreach ($netflix_movie['ITEMS'] as $netflix_movie) {
                    if (!isset($netflix_movie['imdbid']) ||
                        empty($netflix_movie['imdbid']) ||
                        !isset($netflix_movie['netflixid']) ||
                        empty($netflix_movie['netflixid'])) {
                        continue;
                    }

                    $netflix_url = env('NETFLIX_TITLE_URL').$netflix_movie['netflixid'];
                    $client = new Client();
                    $response = $client->request('GET', $netflix_url);
                    $html = $response->getBody()->getContents();

                    /*
                     * Workaround: For API problems. I assume that movies in Germany must have at least one German subtitle or German audio.
                     * That should minimize the issues; that's not works atm for series.
                     */
                    $series = false;
                    preg_match_all('/<h2 class="section-header-text section-item" data-uia="section-header-text">(.*?)<\/h2>/s', $html,
                        $matches);
                    if (isset($matches[1]) && is_array($matches[1])) {

                        // Find something german
                        foreach ($matches[1] AS $match) {

                            if (Str::contains($match, 'Episodes') === true) {
                                $series = true;
                            }

                        }

                    }

                    $german_audio = false;
                    preg_match_all('/<span class="more-details-item item-audio" data-uia="more-details-item-audio">(.*?)<\/span>/s', $html,
                        $matches);
                    if (isset($matches[1]) && is_array($matches[1])) {

                        // Find something german
                        foreach ($matches[1] AS $match) {

                            if (Str::contains($match, 'German') === true) {
                                $german_audio = true;
                            }

                        }

                    }

                    $german_subtitle = false;
                    preg_match_all('/<span class="more-details-item item-subtitle" data-uia="more-details-item-subtitle">(.*?)<\/span>/s',
                        $html,
                        $matches);
                    if (isset($matches[1]) && is_array($matches[1])) {

                        // Find something german
                        foreach ($matches[1] AS $match) {

                            if (Str::contains($match, 'German') === true) {
                                $german_subtitle = true;
                            }

                        }

                    }

                    if ($german_audio === true || $german_subtitle === true || $series === true) {

                        try {
                            $imdb = new Title($netflix_movie['imdbid'], $config);
                            $genre_en = implode(',', $imdb->genres());
                        } catch (\Imdb\Exception\Http $e) {
                            continue;
                        }

                        usleep(10);


                        if (isset($genre_en) && Str::contains($genre_en, 'Horror')) {
                            $netflix_movie_translation = DB::table('netflix_movie_translation')->where('imdbid',
                                $netflix_movie['imdbid'])->first();

                            if (!isset($netflix_movie_translation->imdbid)) {
                                $translated_synopsis = $google_translator->translate('en', 'de', $netflix_movie['synopsis']);
                                sleep(2);
                                $translated_genre = $google_translator->translate('en', 'de', $genre_en);

                                $insert_data = [
                                    'id' => null,
                                    'imdbid' => $netflix_movie['imdbid'],
                                    'genre_en' => $genre_en,
                                    'genre' => $translated_genre,
                                    'description' => $translated_synopsis,
                                    'description_en' => $netflix_movie['synopsis'],
                                ];

                                DB::table('netflix_movie_translation')->insert($insert_data);
                            }

                            if (!empty($translated_synopsis)) {
                                $netflix_movie['synopsis'] = $netflix_movie_translation->description; // just overwrite
                            }

                            $insert_data = [
                                'id' => null,
                                'netflixid' => $netflix_movie['netflixid'],
                                'title' => html_entity_decode($netflix_movie['title'], ENT_QUOTES, 'UTF-8'),
                                'image' => proximage($netflix_movie['image'])->width(474)->get(),
                                'released' => $netflix_movie['released'],
                                'runtime' => $netflix_movie['runtime'],
                                'date' => $netflix_movie['unogsdate'],
                                'imdbid' => $netflix_movie['imdbid'],
                                'rating' => round($imdb->rating()),
                                'type' => $imdb->movietype(),
                            ];

                            DB::table('netflix_movie')->insert($insert_data);
                        }

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
                env('NETFLIX_API_HEADER_HOST_KEY') => env('NETFLIX_API_HEADER_HOST_VALUE'),
                env('NETFLIX_API_HEADER_SECRET_KEY') => env('NETFLIX_API_HEADER_SECRET_VALUE'),
            ],
        ]);

        $response = $result->getBody();

        if (isset($response)) {
            $netflix_expired_movies = json_decode($response, true);
            unset($response);

            foreach ($netflix_expired_movies['ITEMS'] as $netflix_expired_movie) {
                if (!isset($netflix_expired_movie['imdbid']) ||
                    empty($netflix_expired_movie['imdbid']) ||
                    !isset($netflix_expired_movie['netflixid']) ||
                    empty($netflix_expired_movie['netflixid'])) {
                    continue;
                }

                $netflix_url = env('NETFLIX_TITLE_URL').$netflix_expired_movie['netflixid'];
                $client = new Client();
                $response = $client->request('GET', $netflix_url);
                $html = $response->getBody()->getContents();

                /*
                 * Workaround: For API problems. I assume that movies in Germany must have at least one German subtitle or German audio.
                 * That should minimize the issues; that's not works atm for series.
                 */
                $series = false;
                preg_match_all('/<h2 class="section-header-text section-item" data-uia="section-header-text">(.*?)<\/h2>/s', $html,
                    $matches);
                if (isset($matches[1]) && is_array($matches[1])) {

                    // Find something german
                    foreach ($matches[1] AS $match) {

                        if (Str::contains($match, 'Episodes') === true) {
                            $series = true;
                        }

                    }

                }

                $german_audio = false;
                preg_match_all('/<span class="more-details-item item-audio" data-uia="more-details-item-audio">(.*?)<\/span>/s', $html,
                    $matches);
                if (isset($matches[1]) && is_array($matches[1])) {

                    // Find something german
                    foreach ($matches[1] AS $match) {

                        if (Str::contains($match, 'German') === true) {
                            $german_audio = true;
                        }

                    }

                }

                $german_subtitle = false;
                preg_match_all('/<span class="more-details-item item-subtitle" data-uia="more-details-item-subtitle">(.*?)<\/span>/s',
                    $html,
                    $matches);
                if (isset($matches[1]) && is_array($matches[1])) {

                    // Find something german
                    foreach ($matches[1] AS $match) {

                        if (Str::contains($match, 'German') === true) {
                            $german_subtitle = true;
                        }

                    }

                }

                if ($german_audio === true || $german_subtitle === true || $series === true) {

                    try {
                        $imdb = new Title($netflix_expired_movie['imdbid'], $config);
                        $genre_en = implode(',', $imdb->genres());
                    } catch (\Imdb\Exception\Http $e) {
                        continue;
                    }

                    usleep(10);


                    if (isset($genre_en) && Str::contains($genre_en, 'Horror')) {
                        $netflix_movie_translation = DB::table('netflix_movie_translation')->where('imdbid',
                            $netflix_expired_movie['imdbid'])->first();

                        if (!isset($netflix_movie_translation->imdbid)) {

                            $translated_synopsis = $google_translator->translate('en', 'de', $netflix_expired_movie['synopsis']);
                            sleep(3);
                            $translated_genre = $google_translator->translate('en', 'de', $genre_en);
                            sleep(1);

                            $insert_data = [
                                'id' => null,
                                'imdbid' => $netflix_expired_movie['imdbid'],
                                'genre_en' => $genre_en,
                                'genre' => $translated_genre,
                                'description' => $translated_synopsis,
                                'description_en' => $netflix_expired_movie['synopsis'],
                            ];

                            DB::table('netflix_movie_translation')->insert($insert_data);
                        }

                        $insert_data = [
                            'id' => null,
                            'netflixid' => $netflix_expired_movie['netflixid'],
                            'title' => html_entity_decode($netflix_expired_movie['title'], ENT_QUOTES, 'UTF-8'),
                            'image' => proximage($netflix_expired_movie['image'])->width(474)->get(),
                            'released' => $netflix_expired_movie['released'],
                            'runtime' => $netflix_expired_movie['runtime'],
                            'expire_date' => $netflix_expired_movie['unogsdate'],
                            'imdbid' => $netflix_expired_movie['imdbid'],
                            'rating' => round($imdb->rating()),
                            'type' => $imdb->movietype(),
                        ];

                        DB::table('netflix_movie')->insert($insert_data);
                    }

                }

            }
        }
    }
}
