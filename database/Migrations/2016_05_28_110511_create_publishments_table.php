<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePublishmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('publishments', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('fb_post_id');
            $table->string('tweet_id');
            $table->string('content', 500);
            $table->integer('twitter_likes');
            $table->integer('twitter_retweets');
            $table->integer('facebook_likes');
            $table->integer('facebook_shares');
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
        Schema::drop('publishments');
    }
}
