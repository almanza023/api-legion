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
        Schema::table('asistencia_clientes', function (Blueprint $table) {
            $table->index('cliente_id', 'asistencia_clientes_cliente_id_idx');
            $table->dropUnique('asistencia_clientes_cliente_id_fecha_asistencia_unique');
            $table->boolean('es_valida')->default(false)->after('observaciones');
            $table->index(['cliente_id', 'fecha_asistencia', 'es_valida'], 'asistencia_clientes_cliente_fecha_valida_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asistencia_clientes', function (Blueprint $table) {
            $table->dropIndex('asistencia_clientes_cliente_fecha_valida_idx');
            $table->dropColumn('es_valida');
            $table->unique(['cliente_id', 'fecha_asistencia']);
            $table->dropIndex('asistencia_clientes_cliente_id_idx');
        });
    }
};
