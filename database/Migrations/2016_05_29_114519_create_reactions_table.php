<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('publishment_id');
            $table->integer('user_id');
            $table->string('screen_name');
            $table->string('tweet_id');
            $table->string('tweet_reply_id');
            $table->string('fb_post_id');
            $table->string('fb_reply_id');
            $table->string('message', 500);
            $table->dateTime('post_date');
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
        Schema::drop('reactions');
    }
}
