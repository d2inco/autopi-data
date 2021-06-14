<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\RawEvents;

class LoadJSONFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autopi:load-json-files
                            {file*}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load JSON files into Raw Table';

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
     * @return int
     */
    public function handle()
    {
        foreach ($this->argument('file') as $v) {
            printf("Loading file: %-90s", realpath($v));
            $payload = file_get_contents($v);
            try {
                $rawRec = RawEvents::create(['raw_data' => $payload, 'filename' => realpath($v)]);
            } catch (\Illuminate\Database\QueryException $e) {
                if (preg_match('/Duplicate entry/', $e->getMessage())) {
                    printf(" - already loaded.");
                } else {
                    printf("\n              Exception: %s\n", $e->getMessage());
                }
            }
            print "\n";
        }

        printf("LoadJSONFiles: End.\n");
        return 0;
    }
}
