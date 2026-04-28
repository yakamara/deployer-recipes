<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Task\Context;
use RuntimeException;

use function dirname;

$baseDir = dirname(DEPLOYER_DEPLOY_FILE);

localhost('local')
    ->set('deploy_path', $baseDir . '/.build')
    ->set('release_path', $baseDir . '/.build/release')
    ->set('current_path', '{{release_path}}')
;

set('branch', static fn () => runLocally('git rev-parse --abbrev-ref HEAD'));

set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --no-scripts --optimize-autoloader --classmap-authoritative');

set('clear_paths', [
    '.idea',
    '.claude',
    'assets',
    'gulpfile.js',
    'tests',
    '.editorconfig',
    '.env.dev',
    '.env.test',
    '.git-blame-ignore-revs',
    '.gitignore',
    '.gitlab-ci.yml',
    '.php-cs-fixer.dist.php',
    'CLAUDE.md',
    'package.json',
    'package-lock.json',
    'phpstan.dist.neon',
    'phpstan.neon',
    'phpunit.dist.xml',
    'psalm.xml',
    'README.md',
    'rector.php',
    'webpack.config.js',
    'yarn.lock',
    'REVISION',
]);

after('deploy:failed', 'deploy:unlock');

task('deploy', [
    'build:start',
    'release',
]);

task('build:start', static function () {
    on(host('local'), static fn () => invoke('build'));
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

task('build:setup', static function () use ($baseDir) {
    if ('local' !== Context::get()->getHost()->getAlias()) {
        throw new RuntimeException('Task "build" can only be called on host "local"');
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

set('assets_package_manager', static function () {
    if (test('[ -f {{release_path}}/yarn.lock ]')) {
        return 'yarn';
    }
    if (test('[ -f {{release_path}}/package-lock.json ]')) {
        return 'npm';
    }

    return null;
});

set('assets_install', static function () {
    $ci = getenv('CI');

    return match (get('assets_package_manager')) {
        'yarn' => 'yarn install --frozen-lockfile' . ($ci ? ' --cache-folder .assets-cache' : ''),
        'npm' => 'npm ci' . ($ci ? ' --cache .assets-cache' : ''),
        default => null,
    };
});

set('assets_build', static function () {
    return match (get('assets_package_manager')) {
        'yarn' => 'yarn build',
        'npm' => 'npm run build',
        default => null,
    };
});

task('build:assets', static function () {
    $install = get('assets_install');

    if (!$install) {
        return;
    }

    cd('{{release_path}}');

    run($install);

    if ($build = get('assets_build')) {
        run($build);
    }
});

task('upload', static function () use ($baseDir) {
    $source = getenv('CI') ? $baseDir : host('local')->get('release_path');

    upload($source . '/', '{{release_path}}', [
        'flags' => '-rltz',
        'options' => [
            '--executability',
            '--exclude', '.assets-cache',
            '--exclude', '.cache',
            '--exclude', '.git',
            '--exclude', '.tools',
            '--exclude', 'deploy.php',
            '--exclude', 'node_modules',
            '--delete',
        ],
    ]);
});
