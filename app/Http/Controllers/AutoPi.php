<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AutoPi extends Controller
{
    protected $api_top = "https://api.autopi.io";

    protected $token = null;
    // @todo This should really be Private
    // protected $deviceId = null;
    public $deviceId = null;

    protected $lastGetStatus = null;

    // @todo This should really be Private
    public function getUrl($url, $data = null, $jsonOrFull = 0)
    {
        $res = Http::withToken($this->token)->get($this->api_top . $url, $data);


        if ($res->failed()) {
            Log::debug('AutoPi::getUrl(): resp:', [
                'successful' => $res->successful(),
                'status' => $res->status(),
                'url' => $url,
            ]);
        }

        if ($jsonOrFull == 0) {
            return $res->json();
        } else {
            return $res;
        }
    }


    public function initialize($counter = 3)
    {
        Log::debug('AutoPi::initialize(): Start');

        $this->token = Cache::get('token', null);

        // Log::debug('AutoPi::initialize():', ['token' => $this->token]);

        if (!$this->token) {
            Log::debug('AutoPi::initialize(): Token - not cached. Obtaining.');

            Cache::forget('device_id');

            // Token not cached, need to log in.

            $loginResponse = Http::post($this->api_top . "/auth/login/", [
                'username' => env('AUTOPI_USER') . "x",
                'email' => env('AUTOPI_EMAIL'),
                'password' => env('AUTOPI_PASS'),
            ]);

            if ($loginResponse->failed()) {
                Log::debug('AutoPi::initialize(): login unsuccessful.', [
                    'status' => $loginResponse->status(),
                ]);
                Log::debug('AutoPi::initialize(): body:', ['body' => $loginResponse->body()]);
                return 1;
            }

            $this->token = $loginResponse['token'];
            Cache::put('token', $this->token, 300);
        } else {
            Log::debug('AutoPi::initialize(): Token obtained from cache, Ensuring validity.');

            $checkValid = $this->getUrl("/auth/user/", null, 1);

            if ($checkValid->failed()) {
                Log::Info('AutoPi::initialize(): validate: validate token failed', [
                    'status' => $checkValid->status(),
                ]);
                Log::debug('AutoPi::initialize(): validate: body:', ['body' => $checkValid->body()]);

                Cache::forget('token');
                if ($counter > 0) {
                    Log::debug('AutoPi::initialize(): validate: will re-auth');
                    $reInit =  $this->initialize($counter = 1);
                    if ($reInit) {
                        Log::Info('AutoPi::initializeToken(): Re-Init failed.', ['counter' => $counter]);
                        return $reInit;
                    }
                } else {
                    Log::debug('AutoPi::initialize(): validate: exhausted retries. failing.');
                    return 1;
                }
            }
        }


        Log::info(('AutoPi::initialize(): Have token, Checking for DeviceId'));

        $this->deviceId = Cache::get('device_id', null);
        if (!$this->deviceId) {
            $deviceIdResp = $this->getUrl("/dongle/devices");

            if (!$deviceIdResp) {
                Log::info("AutoPi::initialize(): could not fetch device-id");
                return 1;
            }
            $this->deviceId = $deviceIdResp[0]['id'];
            Cache::put('device_id', $this->deviceId, 10);
        } else {
            Log::info('AutoPi::initialize(): Have token, and DeviceID.');
        }
        Log::debug('AutoPi::initialize(): device id: ', ['device_id' => $this->deviceId]);
    }

    private function consolidateResults($r)
    {
        foreach ($r['results'] as $k => $v) {
            // print_r($v); exit(1);
            foreach ($v['data'] as $vk => $vv) {
                $r['data'][$k][$vk] = $vv;
            }
            $r['data'][$k]['ts'] = $v['ts'];
            unset($v['data']);
        }

        unset($r['results']);

        return $r;
    }


    public function fuel(
        $num = 1, // number to return.
        $start = -999,
        $end = -999
    ) {

        if ($start == -999) {
            $start = \Carbon\CarbonImmutable::now()->subDays(7)->format('Y-m-d H:i:s');
        }
        if ($end == -999) {
            $end = \Carbon\CarbonImmutable::now()->format('Y-m-d H:i:s');
        }

        Log::debug('AutoPi::fuel(): start:', [
            'num' => $num,
            'start' => $start,
            'end' => $end,
        ]);

        $fuel = $this->getUrl(
            "/logbook/storage/raw",
            [
                'device_id' => $this->deviceId,
                'start_utc' => $start,
                'end_utc' => $end,
                'data_type' => 'obd.fuel_level',
                'page_num' => 0,
                'page_size' => $num,
            ]
        );

        $fuel = $this->consolidateResults($fuel);

        return $fuel;
    }


    public function position(
        $num = 1, // number to return.
        $start = -999,
        $end = -999
    ) {
        if ($start == -999) {
            $start = \Carbon\CarbonImmutable::now()->subDays(7)->format('Y-m-d H:i:s');
        }
        if ($end == -999) {
            $end = \Carbon\CarbonImmutable::now()->format('Y-m-d H:i:s');
        }

        Log::debug('AutoPi::position(): start', [
            'num' => $num,
            'start' => $start,
            'end' => $end,
        ]);

        $fuel = $this->getUrl(
            "/logbook/storage/raw",
            [
                'device_id' => $this->deviceId,
                'start_utc' => $start,
                'end_utc' => $end,
                'data_type' => 'track.pos',
                'page_num' => 0,
                'page_size' => $num,
            ]
        );

        $fuel = $this->consolidateResults($fuel);

        return $fuel;
    }
}
