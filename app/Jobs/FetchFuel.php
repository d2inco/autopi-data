<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\AutoPi;

class FetchFuel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $autoPi = new AutoPi;
        Log::debug('FetchFuel(): start');

        $result = $autoPi->initialize();
        if ($result > 0) {
            Log::debug("FetchFuel(): initialize failed.", ['init-token result' => $result]);
            return $result;
        }

        $fuelLevels = $autoPi->fuel(3);

        foreach ($fuelLevels['data'] as $f) {
            printf("%s:  %7.3f   %s\n", $f['ts'], $f['value'], $f['unit']);
        }
        print "\n";


        $positions = $autoPi->position(4);
        printf("Total positions: %d\n", $positions['count']);
        foreach ($positions['data'] as $p) {
            printf(
                "%s: at %s   %8.5f, %10.5f  %6.1fm\n",
                $p['ts'],
                $p['utc'],
                $p['loc']['lat'],
                $p['loc']['lon'],
                $p['alt'],
            );
        };
    }
}
