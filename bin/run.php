<?php

declare(strict_types=1);

use App\MainRunner;
use App\StdoutLogger;

require_once dirname(__DIR__ ) . '/vendor/autoload.php';


$gen = static function () {
    for ($i = 0; $i < 100; $i++) {
        yield [
            'id' => 'sum/sleep',
            'a' => $i,
            'b' => random_int(100, 1000),
            'sleep' => random_int(1, 5),
        ];
    }
};

exit((new MainRunner(new StdoutLogger()))->run($gen(), 10));
