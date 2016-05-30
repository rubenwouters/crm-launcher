<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCasesTable extends Migration
{

    public function up()
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('contact_id');
            $table->string('latest_tweet_id');
            $table->string('latest_fb_id');
            $table->string('origin');
            $table->string('latest_helper');
            $table->integer('status');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('cases');
    }
}
