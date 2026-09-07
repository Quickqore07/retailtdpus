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
        Schema::create('employee_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->string('alias_employee_id')->nullable();
            $table->string('alias_name')->nullable();
            $table->timestamps();

            $table->index('alias_employee_id');
            $table->index('alias_name');
        });

        $now = now();
        $employees = DB::table('employee')->select(
            'id',
            'employee_id_1',
            'employee_id_2',
            'employee_id_3',
            'employee_name_1',
            'employee_name_2',
            'employee_name_3'
        )->get();

        $rows = [];
        foreach ($employees as $employee) {
            for ($i = 1; $i <= 3; $i++) {
                $aliasId = $employee->{'employee_id_' . $i} ?? null;
                $aliasName = $employee->{'employee_name_' . $i} ?? null;
                $aliasId = is_string($aliasId) ? trim($aliasId) : $aliasId;
                $aliasName = is_string($aliasName) ? trim($aliasName) : $aliasName;

                if (($aliasId !== null && $aliasId !== '') || ($aliasName !== null && $aliasName !== '')) {
                    $rows[] = [
                        'employee_id' => $employee->id,
                        'alias_employee_id' => $aliasId !== null && $aliasId !== '' ? $aliasId : null,
                        'alias_name' => $aliasName !== null && $aliasName !== '' ? $aliasName : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (!empty($rows)) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('employee_aliases')->insert($chunk);
            }
        }

        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn([
                'employee_name_1',
                'employee_name_2',
                'employee_name_3',
                'employee_id_1',
                'employee_id_2',
                'employee_id_3',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->string('employee_name_1')->nullable();
            $table->string('employee_name_2')->nullable();
            $table->string('employee_name_3')->nullable();
            $table->string('employee_id_1')->nullable();
            $table->string('employee_id_2')->nullable();
            $table->string('employee_id_3')->nullable();
        });

        $aliases = DB::table('employee_aliases')
            ->orderBy('id')
            ->get()
            ->groupBy('employee_id');

        foreach ($aliases as $employeeId => $rows) {
            $update = [];
            foreach ($rows->take(3)->values() as $index => $alias) {
                $i = $index + 1;
                $update['employee_id_' . $i] = $alias->alias_employee_id;
                $update['employee_name_' . $i] = $alias->alias_name;
            }
            if (!empty($update)) {
                DB::table('employee')->where('id', $employeeId)->update($update);
            }
        }

        Schema::dropIfExists('employee_aliases');
    }
};
