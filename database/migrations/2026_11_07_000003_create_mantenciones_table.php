<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->date('fecha');
            $table->decimal('horometro', 10, 1);
            $table->unsignedInteger('costo_clp');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            // Reporte por equipo y rango de fechas
            $table->index(['equipo_id', 'fecha']);
            // Última mantención preventiva de un equipo (MAX(horometro) por tipo)
            $table->index(['equipo_id', 'tipo', 'horometro']);
        });

        DB::statement('ALTER TABLE mantenciones ADD CONSTRAINT chk_mantenciones_horometro CHECK (horometro >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenciones');
    }
};