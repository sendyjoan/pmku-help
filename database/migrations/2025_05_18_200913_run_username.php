<?php

use App\Models\User;
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
        // Generate username untuk user yang sudah ada
        User::whereNull('username')->orWhere('username', '')->each(function ($user) {
            $baseUsername = strtolower(explode('@', $user->email)[0]);
            $baseUsername = preg_replace('/[^a-z0-9_]/', '', $baseUsername);

            $username = $baseUsername;
            $counter = 1;

            while (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            $user->update(['username' => $username]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};