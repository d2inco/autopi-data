<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('raw_id')->constrained('raw_events')->onDelete('cascade');

            $table->datetimetz('ts', $precision = 6); // @ts - time of event on device
            $table->string('event_type'); // @t - event type
            $table->string('event_tag'); // @tag - event tag
            $table->datetimetz('rec', $precision = 6); // @rec - time event received in cloud
            // ignore device id, @uid
            // ignore the vehicle id, @vid

            $table->string("trigger_desc"); // @trigger.description

            $table->text("event_data");


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}
