<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargas_combustible', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->dateTime('fecha_hora');
            $table->decimal('litros', 8, 2);
            $table->unsignedInteger('monto_clp');
            $table->decimal('horometro', 10, 1);
            $table->timestamps();

            // El reporte filtra siempre por equipo y rango de fechas
            $table->index(['equipo_id', 'fecha_hora']);
        });

        DB::statement('ALTER TABLE cargas_combustible ADD CONSTRAINT chk_cargas_litros CHECK (litros > 0)');
        DB::statement('ALTER TABLE cargas_combustible ADD CONSTRAINT chk_cargas_horometro CHECK (horometro >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cargas_combustible');
    }
};