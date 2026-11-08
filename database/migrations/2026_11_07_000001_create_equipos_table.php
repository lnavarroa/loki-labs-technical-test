<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_interno', 50)->unique();
            $table->string('tipo', 50);
            $table->string('patente', 10)->nullable()->unique();
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->decimal('horometro_actual', 10, 1)->default(0);
            $table->string('estado', 20)->default('operativo');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE equipos ADD CONSTRAINT chk_equipos_horometro CHECK (horometro_actual >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};