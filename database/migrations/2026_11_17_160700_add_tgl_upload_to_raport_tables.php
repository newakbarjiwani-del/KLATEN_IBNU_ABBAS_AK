<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akademik_rapot', function (Blueprint $table) {
            $table->date('tgl_upload')->nullable()->after('UJIAN');
        });

        Schema::table('akademik_rapot_tahfidzh', function (Blueprint $table) {
            $table->date('tgl_upload')->nullable()->after('UJIAN');
        });
    }

    public function down(): void
    {
        Schema::table('akademik_rapot', function (Blueprint $table) {
            $table->dropColumn('tgl_upload');
        });

        Schema::table('akademik_rapot_tahfidzh', function (Blueprint $table) {
            $table->dropColumn('tgl_upload');
        });
    }
};
