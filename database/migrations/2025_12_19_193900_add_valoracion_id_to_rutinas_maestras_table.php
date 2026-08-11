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
        Schema::table('rutinas_maestras', function (Blueprint $table) {
            $table->unsignedBigInteger('valoracion_id')->after('entrenador_id');
            
            $table->foreign('valoracion_id')
                  ->references('id')
                  ->on('valoraciones')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
            
            $table->index('valoracion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rutinas_maestras', function (Blueprint $table) {
            $table->dropForeign(['valoracion_id']);
            $table->dropIndex(['valoracion_id']);
            $table->dropColumn('valoracion_id');
        });
    }
};
