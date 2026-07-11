<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('teacher_name')->constrained('teachers')->onDelete('set null');
        });

        // Backfill: one row per distinct (trimmed) teacher_name already used in classes,
        // then point every class at its matching teacher.
        $distinctNames = DB::table('classes')
            ->whereNotNull('teacher_name')
            ->pluck('teacher_name')
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->values();

        foreach ($distinctNames as $name) {
            $teacherId = DB::table('teachers')->where('name', $name)->value('id');

            if (!$teacherId) {
                $teacherId = DB::table('teachers')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('classes')->where('teacher_name', $name)->update(['teacher_id' => $teacherId]);
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('teacher_name');
        });
    }

    public function down()
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('teacher_name')->nullable()->after('class_name');
        });

        DB::table('classes')->update([
            'teacher_name' => DB::raw('(select name from teachers where teachers.id = classes.teacher_id)'),
        ]);

        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
