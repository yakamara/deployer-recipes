<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Task\Context;

$baseDir = dirname(DEPLOYER_DEPLOY_FILE);

localhost('local')
    ->set('deploy_path', $baseDir.'/.build')
    ->set('release_path', $baseDir.'/.build/release')
    ->set('current_path', '{{release_path}}')
;

set('branch', fn () => runLocally('git rev-parse --abbrev-ref HEAD'));

set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --no-scripts --optimize-autoloader --classmap-authoritative');

set('clear_paths', [
    '.idea',
    'assets',
    'gulpfile.js',
    'tests',
    '.gitignore',
    '.gitlab-ci.yml',
    '.php-cs-fixer.dist.php',
    'package.json',
    'psalm.xml',
    'README.md',
    'webpack.config.js',
    'yarn.lock',
    'REVISION',
]);

after('deploy:failed', 'deploy:unlock');

task('deploy', [
    'build:start',
    'release',
]);

task('build:start', function () {
    on(host('local'), fn () => invoke('build'));
})->once()->hidden();

task('build', [
    'deploy:info',
    'build:setup',
    'build:assets',
    'deploy:vendors',
    'deploy:clear_paths',
]);

task('release', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:copy_dirs',
    'upload',
    'deploy:shared',
    'deploy:writable',
    'deploy:cache:clear',
    'database:migrate',
    'deploy:publish',
]);

task('build:setup', function () use ($baseDir) {
    if ('local' !== Context::get()->getHost()->getAlias()) {
        throw new \RuntimeException('Task "build" can only be called on host "local"');
    }

    if (getenv('CI')) {
        set('deploy_path', $baseDir);
        set('release_path', $baseDir);

        return;
    }

    run('rm -rf {{release_path}}');
    run('mkdir -p {{release_path}}');

    invoke('deploy:update_code');
});

set('assets_install', 'yarn');
set('assets_build', 'yarn build');

task('build:assets', function () {
    $install = get('assets_install');

    if (!$install) {
        return;
    }

    cd('{{release_path}}');

    $isLocal = !getenv('CI');
    if ($isLocal && test('[ -d {{deploy_path}}/.node_modules ]')) {
        run('mv {{deploy_path}}/.node_modules node_modules');
    }

    run($install);

    if ($build = get('assets_build')) {
        run($build);
    }

    if ($isLocal) {
        run('mv node_modules {{deploy_path}}/.node_modules');
    }
});

task('upload', function () use ($baseDir) {
    $source = getenv('CI') ? $baseDir : host('local')->get('release_path');

    upload($source.'/', '{{release_path}}', [
        'flags' => '-rltz',
        'options' => [
            '--executability',
            '--exclude', '.cache',
            '--exclude', '.git',
            '--exclude', '.tools',
            '--exclude', 'deploy.php',
            '--exclude', 'node_modules',
            '--delete',
        ],
    ]);
});
