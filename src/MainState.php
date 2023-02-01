<?php

declare(strict_types=1);

namespace App;

enum MainState
{
    case INIT;
    case CYCLE;
    case CLOSING;
    case EXIT;
}
