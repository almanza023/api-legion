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
        // Modificar la tabla para asegurar que el id sea AUTO_INCREMENT
        Schema::table('detalle_rutina_maestra', function (Blueprint $table) {
            // Cambiar el id a AUTO_INCREMENT si no lo está
            $table->bigIncrements('id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios si es necesario
        Schema::table('detalle_rutina_maestra', function (Blueprint $table) {
            //
        });
    }
};
