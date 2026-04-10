<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // drop FK if exists
        DB::statement('ALTER TABLE model_has_roles DROP CONSTRAINT IF EXISTS model_has_roles_model_id_foreign');
        DB::statement('ALTER TABLE model_has_roles DROP CONSTRAINT IF EXISTS model_has_roles_role_id_foreign');

        // convert to UUID
        DB::statement('ALTER TABLE model_has_roles ALTER COLUMN model_id TYPE uuid USING model_id::uuid');
        DB::statement('ALTER TABLE model_has_roles ALTER COLUMN role_id TYPE uuid USING role_id::uuid');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE model_has_roles ALTER COLUMN model_id TYPE bigint USING model_id::bigint');
        DB::statement('ALTER TABLE model_has_roles ALTER COLUMN role_id TYPE bigint USING role_id::bigint');
    }
};