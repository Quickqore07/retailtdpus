<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code', 6)->nullable()->unique()->after('username');
        });

        $users = DB::table('users')->whereNull('user_code')->orderBy('id')->get(['id']);
        $usedCodes = [];

        foreach ($users as $user) {
            do {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (isset($usedCodes[$code]));

            $usedCodes[$code] = true;

            DB::table('users')->where('id', $user->id)->update([
                'user_code' => $code,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['user_code']);
            $table->dropColumn('user_code');
        });
    }
};
