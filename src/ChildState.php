<?php

declare(strict_types=1);

namespace App;

enum ChildState
{
    case INIT;
    case READY;
    case WORKING;
    case SEND_RESULT;
    case EXIT;
}
