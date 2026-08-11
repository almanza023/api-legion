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
        Schema::create('sms_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->integer('dias')->comment('Días antes/después del evento para enviar el SMS');
            $table->text('mensaje')->comment('Contenido del mensaje SMS');
            $table->set('estado', ['activo', 'inactivo'])->default('activo');
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_configuraciones');
    }
};
