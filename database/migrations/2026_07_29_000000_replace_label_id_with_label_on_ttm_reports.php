<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ttm_reports', function (Blueprint $table) {
            $table->string('label')->nullable()->after('label_id');
        });

        // DB::table('ttm_reports')
        //     ->whereNotNull('label_id')
        //     ->orderBy('id')
        //     ->chunkById(500, function ($reports) {
        //         $labels = DB::table('pandl_configuration_details')
        //             ->whereIn('id', $reports->pluck('label_id')->unique())
        //             ->pluck('label', 'id');

        //         foreach ($reports as $report) {
        //             DB::table('ttm_reports')
        //                 ->where('id', $report->id)
        //                 ->update(['label' => $labels[$report->label_id] ?? null]);
        //         }
        //     });

        Schema::table('ttm_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('label_id');
        });
    }

    public function down(): void
    {
        Schema::table('ttm_reports', function (Blueprint $table) {
            $table->foreignId('label_id')
                ->nullable()
                ->after('month')
                ->constrained('pandl_configuration_details')
                ->nullOnDelete();
        });

        // DB::table('ttm_reports')
        //     ->whereNotNull('label')
        //     ->orderBy('id')
        //     ->chunkById(500, function ($reports) {
        //         $labelIds = DB::table('pandl_configuration_details')
        //             ->whereIn('label', $reports->pluck('label')->unique())
        //             ->pluck('id', 'label');

        //         foreach ($reports as $report) {
        //             DB::table('ttm_reports')
        //                 ->where('id', $report->id)
        //                 ->update(['label_id' => $labelIds[$report->label] ?? null]);
        //         }
        //     });

        Schema::table('ttm_reports', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
