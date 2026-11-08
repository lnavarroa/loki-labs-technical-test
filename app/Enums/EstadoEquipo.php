<?php

namespace App\Enums;

enum EstadoEquipo: string
{
    case Operativo = 'operativo';
    case EnMantencion = 'en_mantencion';
    case FueraDeServicio = 'fuera_de_servicio';
}