<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    public function up()
    {
        Schema::create('contacts', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('following');
            $table->string('twitter_handle');
            $table->string('twitter_id');
            $table->string('facebook_id');
            $table->string('profile_picture');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('contacts');
    }
}
