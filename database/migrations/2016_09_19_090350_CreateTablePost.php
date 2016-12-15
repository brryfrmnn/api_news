<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->unsignedInteger('category_id');
            $table->text('article');
            $table->tinyInteger('status'); // 0 = waiting 1 = publish 2 = draft 3 = suspend
            $table->string('slug')->unique();
            $table->unsignedInteger('writer_id');
            $table->unsignedInteger('editor_id')->nullable();
            $table->unsignedInteger('admin_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('posts', function($table) {
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('posts');
    }
}
