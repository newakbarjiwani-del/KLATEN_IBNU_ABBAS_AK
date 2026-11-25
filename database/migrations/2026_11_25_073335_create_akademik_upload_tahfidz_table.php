<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akademik_upload_tahfidz', function (Blueprint $table) {
            $table->increments('urut');
            $table->date('tgl')->nullable();
            $table->string('periode', 10)->nullable();
            $table->string('NOCUST', 15)->nullable();
            $table->string('juzzdihapal', 100)->nullable();
            $table->string('juzzposisi', 20)->nullable();
            $table->string('juzztotal', 20)->nullable();
            $table->string('ziadah', 20)->nullable();
            $table->string('murojaah', 50)->nullable();
            $table->string('tuhfah', 50)->nullable();
            $table->string('jazari', 50)->nullable();
            $table->string('statusujian', 30)->nullable();
            $table->string('prestasi', 50)->nullable();
            $table->string('jumlahpertemuan', 20)->nullable();
            $table->string('totalkehadiran', 20)->nullable();
            $table->index('periode');
            $table->index('NOCUST');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akademik_upload_tahfidz');
    }
};
