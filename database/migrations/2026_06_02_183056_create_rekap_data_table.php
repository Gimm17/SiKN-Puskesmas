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
        Schema::create('rekap_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desa')->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');

            $table->unsignedSmallInteger('lahir_hidup_l')->default(0);
            $table->unsignedSmallInteger('lahir_hidup_p')->default(0);
            $table->unsignedSmallInteger('kn_lengkap_l')->default(0);
            $table->unsignedSmallInteger('kn_lengkap_p')->default(0);

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();

            $table->unique(['desa_id', 'bulan', 'tahun']);
            $table->index(['bulan', 'tahun']);
            $table->index('desa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_data');
    }
};
