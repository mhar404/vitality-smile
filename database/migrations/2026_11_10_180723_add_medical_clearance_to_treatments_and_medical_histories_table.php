<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('treatments')) {
            Schema::table('treatments', function (Blueprint $table) {
                if (!Schema::hasColumn('treatments', 'medical_clearance_presented')) {
                    $table->boolean('medical_clearance_presented')->default(false)->after('prescription');
                }
                if (!Schema::hasColumn('treatments', 'medical_clearance_doctor')) {
                    $table->string('medical_clearance_doctor')->nullable()->after('medical_clearance_presented');
                }
                if (!Schema::hasColumn('treatments', 'medical_clearance_date')) {
                    $table->date('medical_clearance_date')->nullable()->after('medical_clearance_doctor');
                }
                if (!Schema::hasColumn('treatments', 'medical_clearance_notes')) {
                    $table->text('medical_clearance_notes')->nullable()->after('medical_clearance_date');
                }
                if (!Schema::hasColumn('treatments', 'medical_clearance_file')) {
                    $table->string('medical_clearance_file')->nullable()->after('medical_clearance_notes');
                }
            });
        }

        if (Schema::hasTable('medical_histories')) {
            Schema::table('medical_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('medical_histories', 'medical_clearance_presented')) {
                    $table->boolean('medical_clearance_presented')->default(false)->after('notes');
                }
                if (!Schema::hasColumn('medical_histories', 'medical_clearance_doctor')) {
                    $table->string('medical_clearance_doctor')->nullable()->after('medical_clearance_presented');
                }
                if (!Schema::hasColumn('medical_histories', 'medical_clearance_date')) {
                    $table->date('medical_clearance_date')->nullable()->after('medical_clearance_doctor');
                }
                if (!Schema::hasColumn('medical_histories', 'medical_clearance_notes')) {
                    $table->text('medical_clearance_notes')->nullable()->after('medical_clearance_date');
                }
                if (!Schema::hasColumn('medical_histories', 'medical_clearance_file')) {
                    $table->string('medical_clearance_file')->nullable()->after('medical_clearance_notes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('treatments')) {
            Schema::table('treatments', function (Blueprint $table) {
                $table->dropColumn([
                    'medical_clearance_presented',
                    'medical_clearance_doctor',
                    'medical_clearance_date',
                    'medical_clearance_notes',
                    'medical_clearance_file',
                ]);
            });
        }

        if (Schema::hasTable('medical_histories')) {
            Schema::table('medical_histories', function (Blueprint $table) {
                $table->dropColumn([
                    'medical_clearance_presented',
                    'medical_clearance_doctor',
                    'medical_clearance_date',
                    'medical_clearance_notes',
                    'medical_clearance_file',
                ]);
            });
        }
    }
};
