<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\RawEvents;

class APIWebhookController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Note: the data comes in as application/json, but it is malformed with, so must
        // be massaged with convert_json_to_structure() before it can be dealtwith.

        // But, since we want quick turnaround, simply capture it, and stick it in the database.

        $payload = $request->getContent();

        /////////////////////////////////////////
        // BEGIN: Capture file to disk
        /////////////////////////////////////////

        if (1 == 1) {
            $fn_base = strftime("tmp/%Y-%m/%Y-%m-%d");

            if (!is_dir($fn_base)) {
                mkdir($fn_base, 03775, TRUE);                    // create dir, recursively.
                chmod($fn_base, 03775);                          // Do this, since mkdir()'s chmod has umask applied to it.
            }
            $fn_base .= strftime("/%Y-%m-%d.%H%M%S");
            $utime = microtime();
            $fn_base .= substr($utime, 1, 5);


            $fn_json = $fn_base . ".json";
            if (!($fp = fopen($fn_json, 'a'))) {
                syslog(LOG_INFO, "Could not open file for writing");
                return;
            }
            fprintf($fp, "%s", $payload);
            fclose($fp);
        }

        /////////////////////////////////////////
        // END: Capture file to disk
        /////////////////////////////////////////


        $rawRec = RawEvents::create(['raw_data' => $payload, 'filename' => realpath($fn_json)]);

        return response("Data Stored\n", 200);
    }
}
