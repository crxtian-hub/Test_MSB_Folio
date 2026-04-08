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
        if (!Schema::hasColumn('projects', 'sort_order')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('cover_image');
            });
        }

        $projectIds = DB::table('projects')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->pluck('id');

        foreach ($projectIds as $index => $projectId) {
            DB::table('projects')
                ->where('id', $projectId)
                ->update([
                    'sort_order' => $index + 1,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('projects', 'sort_order')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
