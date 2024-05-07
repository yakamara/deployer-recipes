<?php

declare(strict_types=1);

namespace Deployer;

require 'phar://'.DEPLOYER_BIN.'/recipe/symfony.php';
require __DIR__.'/base.php';

set('copy_dirs', [
    'bin',
    'config',
    'migrations',
    'public',
    'src',
    'templates',
    'translations',
    'vendor',
]);

task('deploy:assets:install', function () {
    run('{{bin/console}} assets:install --symlink {{console_options}}');
});
after('deploy:cache:clear', 'deploy:assets:install');

task('deploy:stop_workers', function () {
    if (!has('previous_release')) {
        return;
    }

    $console = '{{bin/php}} {{previous_release}}/bin/console';
    if (test('[[ $('.$console.' list messenger --raw {{console_options}} | grep messenger:stop-workers) ]]')) {
        run($console.' messenger:stop-workers {{console_options}}');
    }
});
after('deploy:symlink', 'deploy:stop_workers');
