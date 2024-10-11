<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('filesystem_config', function (Blueprint $table) {
            $table->string('path_gen_template')->default('{date}/{type}')->comment('路径生成规则');
            $table->string('name_gen_template')->default('{rand(32)}-{uuid}.{ext}')->comment('文件名生成规则');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('filesystem_config',function(Blueprint $table){
            $table->removeColumn('path_gen_template');
            $table->removeColumn('name_gen_template');
        });
    }
};
