<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersCasesTable extends Migration
{
    public function up()
    {
        Schema::create('users_cases', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('case_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('users_cases');
    }
}
