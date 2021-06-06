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

        $rawRec = RawEvents::create(['raw_data' => $payload]);

        return response("Data Stored\n", 200);
    }
}
