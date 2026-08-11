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
        Schema::table('ejercicios', function (Blueprint $table) {
            $table->boolean('es_estiramiento')->default(false)->after('maquina_id');
            $table->unsignedBigInteger('grupo_muscular_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ejercicios', function (Blueprint $table) {
            $table->dropColumn('es_estiramiento');
            // Note: reversing nullable change might fail if there are null values
            // $table->unsignedBigInteger('grupo_muscular_id')->nullable(false)->change();
        });
    }
};
