<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{

    /**
     * @var array
     */
    protected $fields;

    public function __construct()
    {
        $this->fields();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function($table) {
                foreach ($this->fields as $field => $value) {
                    if(!Schema::hasColumn('users', $field)) {
                        $type = $value['type'];
                        $table->$type($field);
                    }
                }
                $table->timestamps();
            });
        } else {
            Schema::create('users', function($table) {
                foreach ($this->fields as $field => $value) {
                    if(!Schema::hasColumn('users', $field)) {
                        $type = $value['type'];
                        $table->$type($field);
                    }
                }
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }

    /**
     * Returns required fields
     * @return array
     */
    private function fields()
    {
        return $this->fields = [
            'id' => [
                'type' => 'increments',
            ],
           'name' => [
               'type' => 'string',
               'length' => 250,
           ],
           'email' => [
               'type' => 'string',
               'length' => 250,
               'extra' => 'unique'
           ],
           'password' => [
               'type' => 'string',
               'length' => 100,
           ],
           'remember_token' => [
               'type' => 'string',
               'length' => 100,
           ],
           'canViewCRM' => [
               'type' => 'integer',
               'length' => 1,
           ]
        ];
    }
}
