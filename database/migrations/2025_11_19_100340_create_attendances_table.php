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
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
        $table->foreignId('cr_id')->constrained('users')->onDelete('cascade');
        $table->date('date');
        $table->time('arrived_time')->nullable();
        $table->time('left_time')->nullable();
        $table->enum('status', ['present','late','absent']);
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
        Schema::dropIfExists('attendances');
    }
};
