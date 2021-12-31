<?php

namespace App\Jobs;

use App\Http\Controllers\AutoPi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Carbon;

use App\Models\Events;

class GetStatsAtMovementChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $event_id = 0;
    protected $ts = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_id, $ts)
    {
        // Log::debug("GetStatsAtMovementChange(): construct(): start");
        $this->event_id = $event_id;
        $this->ts = $ts;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Log::debug("GetStatsAtMovementChange(): handle(): start");

        $autoPi = new AutoPi;
        $result = $autoPi->initialize();
        if ($result > 0) {
            Log::debug("GetStatsAtMovementChange(): AutoPi Initialize failed.", ['init-token result' => $result]);
            return $result;
        }

        $e = Events::find($this->event_id);

        $start = (new Carbon($this->ts))->subMinutes(10)->format('Y-m-d H:i:s');

        if (0 == 1) {
            Log::debug('GetStatsAtMovementChange:', ['e' => $e]);
            Log::debug('GetStatsAtMovementChange:', ['t1' => $this->ts]);
            Log::debug('GetStatsAtMovementChange:', ['t0' => $start]);
        }

        if (is_null($e->extra_data)) {
            // Log::debug('GetStatsAtMovementChange: e->extra IS Null', ['e[extra_data]' => $e->extra_data]);
            $e->extra_data = array();
        } else {
            // Log::debug('GetStatsAtMovementChange: e->extra IS NOT Null', ['e[extra_data]' => $e->extra_data]);
        }

        $extra_data = $e->extra_data;

        $fuelLevels = $autoPi->fuel(1, $start, $this->ts,);

        // print_r($fuelLevels);

        if ($fuelLevels['count'] > 0) {
            $extra_data['fuel'] = (float) sprintf("%5.3f", $fuelLevels['data'][0]['value']);
            $extra_data['fuel_utc'] = $fuelLevels['data'][0]['ts'];
        }

        $positions = $autoPi->position(1, $start, $this->ts,);

        // print_r($positions);

        if ($positions['count'] > 0) {
            $extra_data['loc'] =  $positions['data'][0]['loc']['lat'] . ", " . $positions['data'][0]['loc']['lon'];
            $extra_data['loc_alt'] =  $positions['data'][0]['alt'];
            $extra_data['loc_utc'] = $positions['data'][0]['ts'];
        }

        $e->extra_data = $extra_data;
        $e->save();

        Log::info('GetStatsAtMovementChange(): end:', [
            'e.id' => $this->event_id,
            'fuel' => $extra_data['fuel'] . '%',
            'pos' => $extra_data['loc'],
            'ts' => $this->ts,
        ]);
    }
}
