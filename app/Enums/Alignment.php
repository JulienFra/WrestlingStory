<?php

namespace App\Enums;

/**
 * The side of a wrestler: the crowd cheers a face and boos a heel.
 */
enum Alignment: string
{
    case Face = 'face';
    case Heel = 'heel';
}
