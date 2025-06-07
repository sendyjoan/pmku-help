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
        Schema::create('customer_feedback_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained('customer_feedbacks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User yang melakukan action
            $table->string('action'); // 'submitted', 'converted_to_ticket', 'rejected', 'noted'
            $table->text('notes')->nullable(); // Catatan dari admin/super admin
            $table->timestamps();

            $table->index('feedback_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_feedback_activities');
    }
};
