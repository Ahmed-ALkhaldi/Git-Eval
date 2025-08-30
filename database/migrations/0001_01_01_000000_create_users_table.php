<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 100)->after('id');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 100)->after('first_name');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['student','supervisor','admin'])->default('student')->after('password');
            }
        });

        // تنظيف أي أعمدة وُضعت بالخطأ سابقًا في users (شرطياً لتجنّب الأعطال)
        Schema::table('users', function (Blueprint $table) {
            $toDrop = [
                'university_num',
                'university_name',
                'enrollment_certificate_path',
                'verification_status',
                'verified_by',
                'verified_at',
                'is_available',
            ];
            foreach ($toDrop as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'first_name')) $table->dropColumn('first_name');
            if (Schema::hasColumn('users', 'last_name'))  $table->dropColumn('last_name');
            if (Schema::hasColumn('users', 'role'))       $table->dropColumn('role');
            // لا نُعيد الأعمدة الخاصة بالطالب/المشرف لأنها نُقلت للجداول المتخصصة
        });
    }
};
