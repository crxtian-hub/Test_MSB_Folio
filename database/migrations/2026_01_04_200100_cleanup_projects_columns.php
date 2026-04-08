<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('projects', 'date')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('date')->nullable()->after('place');
            });
        }

        if (Schema::hasColumn('projects', 'datee')) {
            DB::statement('update projects set date = datee where date is null');

            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('datee');
            });
        }

        if (Schema::hasColumn('projects', 'description')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('projects', 'description')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->text('description')->nullable()->after('slug');
            });
        }

        if (!Schema::hasColumn('projects', 'datee')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('datee')->nullable()->after('place');
            });
        }

        if (Schema::hasColumn('projects', 'date')) {
            DB::statement('update projects set datee = date where datee is null');

            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('date');
            });
        }
    }
};
