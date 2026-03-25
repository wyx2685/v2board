<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerUserTable extends Migration
{
    public function up()
    {
        Schema::create('v2_server_user', function (Blueprint $table) {
            $table->unsignedInteger('server_id');
            $table->string('server_type', 32);
            $table->unsignedInteger('user_id');
            $table->primary(['server_id', 'server_type', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('v2_server_user');
    }
}
