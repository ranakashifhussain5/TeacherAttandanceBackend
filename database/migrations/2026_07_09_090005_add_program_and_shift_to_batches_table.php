<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropUnique('batches_start_year_end_year_unique');
            $table->foreignId('program_id')->nullable()->after('id')->constrained('programs')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->after('program_id')->constrained('shifts')->onDelete('cascade');
            $table->unique(['program_id', 'shift_id', 'start_year', 'end_year']);
        });
    }

    public function down()
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropUnique(['program_id', 'shift_id', 'start_year', 'end_year']);
            $table->dropConstrainedForeignId('program_id');
            $table->dropConstrainedForeignId('shift_id');
            $table->unique(['start_year', 'end_year']);
        });
    }
};
