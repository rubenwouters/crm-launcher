<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configurations', function(Blueprint $table) {
            $table->increments('id');
            $table->string('twitter_screen_name');
            $table->string('twitter_id');
            $table->string('facebook_access_token');
            $table->integer('facebook_likes');
            $table->integer('twitter_followers');
            $table->integer('valid_credentials');
            $table->integer('linked_facebook');
            $table->integer('linked_twitter');
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
        Schema::drop('configurations');
    }
}
