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
        // Menambahkan setting untuk site_logo_dark
        DB::table('settings')->insert([
            'group' => 'general',
            'name' => 'site_logo_dark',
            'locked' => 0,
            'payload' => json_encode(null),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Hapus setting site_logo_dark
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'site_logo_dark')
            ->delete();
    }
};
