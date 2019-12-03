<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
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

        print_r($files);

    }

}
