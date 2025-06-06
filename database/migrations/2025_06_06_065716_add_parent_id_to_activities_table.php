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
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->integer('level')->default(0)->after('parent_id');
            $table->integer('sort_order')->default(0)->after('level');

            $table->foreign('parent_id')->references('id')->on('activities')->onDelete('cascade');
            $table->index(['parent_id', 'level']);
        });
    }

    public function down()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'level', 'sort_order']);
        });
    }
};