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
        Schema::create('customer_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Client yang submit
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['pending', 'converted_to_ticket', 'rejected'])->default('pending');
            $table->foreignId('converted_ticket_id')->nullable()->constrained('tickets')->onDelete('set null');
            $table->timestamps();

            $table->index(['project_id', 'user_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_feedbacks');
    }
};
