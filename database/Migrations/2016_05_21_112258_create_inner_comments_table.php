<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInnerCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inner_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('contact_id');
            $table->integer('message_id');
            $table->integer('reaction_id');
            $table->integer('user_id');
            $table->integer('answer_id');
            $table->string('fb_post_id');
            $table->string('fb_reply_id');
            $table->string('message');
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
        Schema::drop('inner_comments');
    }
}
