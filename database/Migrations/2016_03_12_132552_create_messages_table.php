<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{

    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('case_id');
            $table->integer('contact_id');
            $table->string('tweet_id');
            $table->string('tweet_reply_id');
            $table->string('direct_id');
            $table->string('fb_post_id');
            $table->string('fb_reply_id');
            $table->string('fb_conversation_id');
            $table->string('fb_private_id');
            $table->string('message', 500);
            $table->dateTime('post_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('messages');
    }
}
