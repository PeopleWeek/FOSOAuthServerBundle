<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__]);
    $rectorConfig->skip(['vendor', 'Tests']);

    $rectorConfig->sets([
        SymfonySetList::SYMFONY_60
    ]);
};
