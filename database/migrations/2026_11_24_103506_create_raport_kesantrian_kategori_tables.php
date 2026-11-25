<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akademik_ref_raport_kategori', function (Blueprint $table) {
            $table->id();
            $table->string('unit', 20);
            $table->string('nama', 80);
            $table->unsignedSmallInteger('urut')->default(1);
            $table->timestamp('deleted_at')->nullable();
            $table->index(['unit', 'deleted_at']);
        });

        Schema::create('akademik_ref_raport_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->string('nama', 80);
            $table->unsignedSmallInteger('urut')->default(1);
            $table->timestamp('deleted_at')->nullable();
            $table->index(['kategori_id', 'deleted_at']);
        });

        Schema::create('akademik_raport_kesantrian_nilai', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('raport_urut');
            $table->unsignedBigInteger('item_id');
            $table->string('nilai', 255)->nullable();
            $table->unique(['raport_urut', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akademik_raport_kesantrian_nilai');
        Schema::dropIfExists('akademik_ref_raport_item');
        Schema::dropIfExists('akademik_ref_raport_kategori');
    }
};
