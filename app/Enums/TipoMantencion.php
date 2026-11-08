<?php

namespace App\Enums;

enum TipoMantencion: string
{
    case Preventiva = 'preventiva';
    case Correctiva = 'correctiva';
}