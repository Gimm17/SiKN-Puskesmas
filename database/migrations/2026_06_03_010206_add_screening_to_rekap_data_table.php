<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekap_data', function (Blueprint $table) {
            $table->unsignedSmallInteger('screening_hipotiroid_l')->default(0)->after('kn_lengkap_p');
            $table->unsignedSmallInteger('screening_hipotiroid_p')->default(0)->after('screening_hipotiroid_l');
        });
    }

    public function down(): void
    {
        Schema::table('rekap_data', function (Blueprint $table) {
            $table->dropColumn(['screening_hipotiroid_l', 'screening_hipotiroid_p']);
        });
    }
};
