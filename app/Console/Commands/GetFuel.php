<?php

namespace App\Console\Commands;

use App\Jobs\FetchFuel;
use Illuminate\Console\Command;

class GetFuel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autopi:get-fuel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the latest Fuel';

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
        FetchFuel::dispatchSync();
        return 0;
    }
}
