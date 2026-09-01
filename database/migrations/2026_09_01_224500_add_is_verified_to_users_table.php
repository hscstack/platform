<?php

use App\Models\User;
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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('email_verified_at');
        });

        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $modelMorphKey = config('permission.column_names.model_morph_key', 'model_id');

        if (Schema::hasTable($modelHasRolesTable)) {
            $userIdsWithRoles = DB::table($modelHasRolesTable)
                ->where(function ($query) {
                    $query->where('model_type', User::class)
                        ->orWhere('model_type', 'App\Models\User');
                })
                ->distinct()
                ->pluck($modelMorphKey);

            if ($userIdsWithRoles->isNotEmpty()) {
                DB::table('users')
                    ->whereIn('id', $userIdsWithRoles)
                    ->update(['is_verified' => true]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_verified');
        });
    }
};
