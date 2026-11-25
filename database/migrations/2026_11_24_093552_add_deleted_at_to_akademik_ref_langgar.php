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
        if (Schema::hasColumn('akademik_ref_langgar', 'deleted_at')) {
            return;
        }

        Schema::table('akademik_ref_langgar', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('akademik_ref_langgar', 'deleted_at')) {
            return;
        }

        Schema::table('akademik_ref_langgar', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
