<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAnswersTable extends Migration
{
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('case_id');
            $table->integer('user_id');
            $table->integer('message_id');
            $table->string('tweet_id');
            $table->string('tweet_reply_id');
            $table->string('fb_post_id');
            $table->string('fb_reply_id');
            $table->string('fb_private_id');
            $table->string('answer', 500);
            $table->dateTime('post_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('answers');
    }
}
