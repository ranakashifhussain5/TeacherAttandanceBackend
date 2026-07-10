<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Widen role before touching data so 'hod' is a legal value.
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'cr'");

        DB::table('users')->where('role', 'admin')->update(['role' => 'hod']);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('role')->constrained('programs')->onDelete('set null');
            $table->foreignId('batch_id')->nullable()->after('program_id')->constrained('batches')->onDelete('set null');
            $table->foreignId('shift_id')->nullable()->after('batch_id')->constrained('shifts')->onDelete('set null');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['department', 'shift', 'start_session', 'end_session']);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable();
            $table->string('shift')->nullable();
            $table->year('start_session')->nullable();
            $table->year('end_session')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropConstrainedForeignId('batch_id');
            $table->dropConstrainedForeignId('shift_id');
        });

        DB::table('users')->where('role', 'hod')->update(['role' => 'admin']);

        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'cr'");
    }
};
