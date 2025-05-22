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
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('auto_complete_enabled')->default(false)->after('type');
            $table->integer('auto_complete_days')->default(3)->after('auto_complete_enabled');
            $table->string('auto_complete_from_status')->nullable()->after('auto_complete_days')->comment('Status name to monitor');
            $table->string('auto_complete_to_status')->nullable()->after('auto_complete_from_status')->comment('Status name to move to');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'auto_complete_enabled',
                'auto_complete_days',
                'auto_complete_from_status',
                'auto_complete_to_status'
            ]);
        });
    }
};
