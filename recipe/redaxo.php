<?php

declare(strict_types=1);

namespace Deployer;

require 'recipe/common.php';
require __DIR__ . '/base.php';

set('bin/console', '{{bin/php}} {{release_or_current_path}}/bin/console');
set('console_options', '--no-interaction');

set('shared_dirs', [
    'public/media',
    'var/data',
    'var/log',
]);

set('shared_files', [
    '.env.local',
]);

set('writable_dirs', [
    'public/media',
    'var/cache',
    'var/data',
    'var/log',
]);

set('copy_dirs', [
    'bin',
    'public',
    'src',
    'vendor',
]);

task('deploy:cache:clear', static function () {
    run('{{bin/console}} cache:clear {{console_options}}');
});

// Runs while the previous release is still live, so the schema changes have to be compatible with it.
task('database:migrate', static function () {
    run('{{bin/console}} migrate {{console_options}}');
});

// Removes the schema of addons that are no longer installed, which is only safe once the new release is live.
task('database:cleanup', static function () {
    run('{{bin/console}} migrate:cleanup {{console_options}}');
});
after('deploy:symlink', 'database:cleanup');
