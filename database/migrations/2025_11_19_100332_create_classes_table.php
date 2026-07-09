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
    Schema::create('classes', function (Blueprint $table) {
        $table->id();
        $table->string('class_name');
        $table->string('teacher_name');
        $table->string('department');
        $table->year('start_session');
        $table->year('end_session');
        $table->enum('day', ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']);
        $table->time('start_time');
        $table->time('end_time');
        $table->string('room');
        $table->foreignId('cr_id')->nullable()->constrained('users')->onDelete('set null');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('classes');
    }
};
