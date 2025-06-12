<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlanIdToV2StaffTable extends Migration
{
    public function up()
    {
        Schema::table('v2_staff', function (Blueprint $table) {
            $table->json('plan_id')->nullable()->after('domain');
        });
    }

    public function down()
    {
        Schema::table('v2_staff', function (Blueprint $table) {
            $table->dropColumn('plan_id');
        });
    }
}
