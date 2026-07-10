<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('teacher_name')->constrained('programs')->onDelete('set null');
            $table->foreignId('batch_id')->nullable()->after('program_id')->constrained('batches')->onDelete('set null');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['department', 'start_session', 'end_session']);
        });
    }

    public function down()
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('department')->nullable();
            $table->year('start_session')->nullable();
            $table->year('end_session')->nullable();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};
