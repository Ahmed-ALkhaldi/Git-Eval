<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (!Schema::hasColumn('evaluations', 'computed_at')) {
                $table->timestamp('computed_at')->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('evaluations', 'summary')) {
                $table->json('summary')->nullable()->after('computed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('evaluations', 'summary')) {
                $table->dropColumn('summary');
            }
            if (Schema::hasColumn('evaluations', 'computed_at')) {
                $table->dropColumn('computed_at');
            }
        });
    }
}; 