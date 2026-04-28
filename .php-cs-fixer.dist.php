<?php

declare(strict_types=1);

use PhpCsFixer\Finder;
use Yakamara\PhpCsFixerConfig\Config;

return Config::php81()
    ->setCacheFile(__DIR__ . '/.cache/php-cs-fixer.cache')
    ->setFinder((new Finder())->in(__DIR__))
;
