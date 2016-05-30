<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSummariesTable extends Migration
{

    public function up()
    {
        Schema::create('summaries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('case_id');
            $table->integer('user_id');
            $table->string('summary');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('summaries');
    }
}
