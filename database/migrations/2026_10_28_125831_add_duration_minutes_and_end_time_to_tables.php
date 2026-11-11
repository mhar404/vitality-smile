<?php

use Carbon\Carbon;
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
        // 1. Add duration_minutes to services table
        if (Schema::hasTable('services') && !Schema::hasColumn('services', 'duration_minutes')) {
            Schema::table('services', function (Blueprint $table) {
                $table->unsignedInteger('duration_minutes')->default(60)->after('duration');
            });
        }

        // 2. Add duration_minutes and end_time to appointments table
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'duration_minutes')) {
                    $table->unsignedInteger('duration_minutes')->default(60)->after('service');
                }
                if (!Schema::hasColumn('appointments', 'end_time')) {
                    $table->time('end_time')->nullable()->after('appointment_time');
                }
            });
        }

        // 3. Populate services with clinical duration minutes
        if (Schema::hasTable('services')) {
            $services = DB::table('services')->get();
            foreach ($services as $srv) {
                $titleLower = strtolower($srv->title . ' ' . $srv->name);
                $duration = 60;

                if (str_contains($titleLower, 'orthodontic') || str_contains($titleLower, 'adjustment')) {
                    $duration = 30;
                } elseif (str_contains($titleLower, 'restoration') || str_contains($titleLower, 'composite') || str_contains($titleLower, 'filling')) {
                    $duration = 45;
                } elseif (str_contains($titleLower, 'routine tooth extraction') || str_contains($titleLower, 'tooth extraction')) {
                    $duration = 45;
                } elseif (str_contains($titleLower, 'odontectomy') || str_contains($titleLower, 'impacted') || str_contains($titleLower, 'root canal')) {
                    $duration = 90;
                } elseif (str_contains($titleLower, 'prophylaxis') || str_contains($titleLower, 'cleaning')) {
                    $duration = 60;
                } elseif (str_contains($titleLower, 'whitening') || str_contains($titleLower, 'bleaching')) {
                    $duration = 60;
                } elseif (str_contains($titleLower, 'crown') || str_contains($titleLower, 'bridge') || str_contains($titleLower, 'denture')) {
                    $duration = 60;
                }

                DB::table('services')->where('id', $srv->id)->update([
                    'duration_minutes' => $duration,
                ]);
            }
        }

        // 4. Backfill existing appointments duration_minutes and end_time
        if (Schema::hasTable('appointments')) {
            $appointments = DB::table('appointments')->get();
            foreach ($appointments as $apt) {
                $duration = $apt->duration_minutes ?: 60;

                // Try to match linked service
                $service = DB::table('services')->where('name', $apt->service)->first();
                if ($service && !empty($service->duration_minutes)) {
                    $duration = $service->duration_minutes;
                }

                $endTime = null;
                if (!empty($apt->appointment_time)) {
                    try {
                        $endTime = Carbon::parse($apt->appointment_time)->addMinutes($duration)->format('H:i:s');
                    } catch (\Throwable $e) {
                        $endTime = Carbon::parse($apt->appointment_time)->addHour()->format('H:i:s');
                    }
                }

                DB::table('appointments')->where('id', $apt->id)->update([
                    'duration_minutes' => $duration,
                    'end_time' => $endTime,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (Schema::hasColumn('appointments', 'end_time')) {
                    $table->dropColumn('end_time');
                }
                if (Schema::hasColumn('appointments', 'duration_minutes')) {
                    $table->dropColumn('duration_minutes');
                }
            });
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (Schema::hasColumn('services', 'duration_minutes')) {
                    $table->dropColumn('duration_minutes');
                }
            });
        }
    }
};
