<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_events', function (Blueprint $table) {
            $table->id();

            $table->text('raw_data');

            $table->boolean('processed')->default(false);
            $table->boolean('duplicate')->unsignedBigInteger()->default(0);

            $table->string('filename')->unique();

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
        Schema::dropIfExists('raw_events');
    }
}
