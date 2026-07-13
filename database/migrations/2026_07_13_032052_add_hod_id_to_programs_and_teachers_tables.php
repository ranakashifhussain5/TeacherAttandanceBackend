<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('hod_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('hod_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->unique(['hod_id', 'name']);
        });
    }

    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['hod_id']);
            $table->dropColumn('hod_id');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique(['hod_id', 'name']);
            $table->dropForeign(['hod_id']);
            $table->dropColumn('hod_id');
            $table->unique('name');
        });
    }
};
