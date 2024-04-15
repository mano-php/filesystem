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
        Schema::create('filesystem_config', function (Blueprint $table) {
            $table->comment('文件系统');
            $table->increments('id');
            $table->string('name')->default('')->comment('名称');
            $table->string('desc')->nullable()->comment('描述');
            $table->string('key',50)->index()->comment('引用标识');
            $table->integer('driver')->index()->comment('驱动');
            $table->text('config')->comment('配置内容');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('filesystem_config');
    }
};
