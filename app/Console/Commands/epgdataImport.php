<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class epgdataImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'epgdata:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'imports epgdata';

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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {

        $epgPin = env('EPG_PIN');

        $epgUrls = [
            'epgPackage' => 'https://www.epgdata.com/index.php?action=sendPackage&iOEM=vdr&pin=' . $epgPin . '&dayOffset=2&dataType=xml',
            'epgInclude' => 'http://www.epgdata.com/index.php?action=sendInclude&iOEM=vdr&pin=' . $epgPin . '&dataType=xml'
        ];

        /*
         * Download and extract files
         */
        foreach ($epgUrls AS $name => $epgUrl) {

            $filename = date('Ymd', strtotime('+ 3 days')) . '_' . $name . '.zip';

            $localFile = storage_path('/tmp/' . $filename);

            if (file_exists(storage_path('/tmp/' . $filename)) === false) {

                $client = new Client();
                $client->request('GET', $epgUrl, [
                    'sink' => $localFile
                ]);

            }

            $zip = new ZipArchive;
            if($zip->open($localFile) === true) {
                $zip->extractTo(storage_path('/tmp/'));
                $zip->close();
            }

        }

        /*
         * Read XML Files and import data into Database
         */
        $files = File::files(storage_path('/tmp'));

        foreach($files AS $file) {

            if (substr($file->getPathname(), -4) === '.xml') {

                $xml = simplexml_load_file($file->getPathname());
                foreach ($xml AS $dom) {

                    if(isset($dom->d0)) {// Movie file

                        $insert_data = [
                            'id' => null,
                            'broadcast_id' => (int) $dom->d0,
                            'tvshow_id' => (int) $dom->d1,
                            'tvchannel_id' => (int) $dom->d2,
                            'tvregionid' => (int) $dom->d3,
                            'starttime' => (string) $dom->d4,
                            'endtime' => (string)$dom->d5,
                            'broadcast_day' => (int) $dom->d6,
                            'tvshow_length' => (int) $dom->d7,
                            'vps' => (int) $dom->d8,
                            'primetime' => (int) $dom->d9,
                            'category_id' => (int)  $dom->d10,
                            'technics_bw' => (int) $dom->d11,
                            'technics_co_channel' => (int) $dom->d12,
                            'technics_vt150' => (int) $dom->d13,
                            'technics_coded' => (int) $dom->d14,
                            'technics_blind' => (int) $dom->d15,
                            'age_marker' => (int) $dom->d16,
                            'live_id' => (int) $dom->d17,
                            'tipflag' => (int) $dom->d18,
                            'title' => (string) $dom->d19,
                            'subtitle' => (string) $dom->d20,
                            'comment_long' => (string) $dom->d21,
                            'comment_middle' => (string) $dom->d22,
                            'comment_short' => (string) $dom->d23,
                            'themes' => (int) $dom->d24,
                            'genreid' => (int) $dom->d25,
                            'sequence' => (int) $dom->d26,
                            'technics_stereo' => (int) $dom->d27,
                            'technics_dolby' => (int) $dom->d28,
                            'technics_wide' => (int) $dom->d29,
                            'tvd_total_value' => (int) $dom->d30,
                            'attribute' => (int) $dom->d31,
                            'country' => (string) $dom->d32,
                            'year' => (int) $dom->d33,
                            'moderator' => (string) $dom->d34,
                            'studio_guest' => (string) $dom->d35,
                            'regisseur' => (string) $dom->d36,
                            'actor' => (string) $dom->d37,
                            'image_small' => (string) $dom->d38,
                            'image_middle' => (string) $dom->d39,
                            'image_big' => (string) $dom->d40

                        ];

                        // Movie data
                        DB::table('epgdata_movies')->insert($insert_data);

                    }

                    if(isset($dom->ca0)) {//Category file

                        $insert_data = [
                            'id' => null,
                            'category1' => (string) $dom->ca0,
                            'category2' => (string) $dom->ca1,
                        ];

                        DB::table('epgdata_categories')->insert($insert_data);

                    }

                    if(isset($dom->ch0)) {//Channel file

                        $insert_data = [
                            'id' => null,
                            'channel_id' => (int) $dom->ch4,
                            'name' => (string) $dom->ch0,
                            'language' => (string) $dom->ch3,
                            'country' => (string) $dom->ch2,
                        ];

                        DB::table('epgdata_channels')->insert($insert_data);

                    }

                    if(isset($dom->g0)) {//Genre file

                        $insert_data = [
                            'id' => null,
                            'genreid' => (int) $dom->g0,
                            'name' => (string) $dom->g0
                        ];

                        DB::table('epgdata_genre')->insert($insert_data);

                    }

                }

            }

        }

    }

}