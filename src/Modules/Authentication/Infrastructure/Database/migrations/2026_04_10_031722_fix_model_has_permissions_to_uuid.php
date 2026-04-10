<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE model_has_permissions DROP CONSTRAINT IF EXISTS model_has_permissions_model_id_foreign');
        DB::statement('ALTER TABLE model_has_permissions DROP CONSTRAINT IF EXISTS model_has_permissions_permission_id_foreign');

        DB::statement('ALTER TABLE model_has_permissions ALTER COLUMN model_id TYPE uuid USING model_id::uuid');
        DB::statement('ALTER TABLE model_has_permissions ALTER COLUMN permission_id TYPE uuid USING permission_id::uuid');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE model_has_permissions ALTER COLUMN model_id TYPE bigint USING model_id::bigint');
        DB::statement('ALTER TABLE model_has_permissions ALTER COLUMN permission_id TYPE bigint USING permission_id::bigint');
    }
};