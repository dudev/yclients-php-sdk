<?php

declare(strict_types=1);

namespace Dudev\YclientsPhpSdk\Record;

/** `Record::$attendance` — documented officially (YClients "Проверить параметры записи" section). */
enum Attendance: int
{
    case Confirmed = 2;
    case Came = 1;
    case Awaiting = 0;
    case NoShow = -1;
}
