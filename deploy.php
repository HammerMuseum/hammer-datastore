<?php
namespace Deployer;

require 'contrib/rsync.php';
require 'recipe/laravel.php';
require 'recipe/tasks.php';

// Project name
set('application', 'Laravel');

// Project repository
set('repository', 'git@github.com:HammerMuseum/hammer-video.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
add('shared_files', ['.env']);
add('shared_dirs', ['storage']);

// Writable dirs by web server
add('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);
set('allow_anonymous_stats', false);

set('http_user', 'www-data');
set('rsync_src', __DIR__);
set('rsync_dest', '{{release_path}}');

set('rsync', [
    'exclude'      => [
        'deploy.php',
        '.git',
        'node_modules',
        '.babelrc',
        '.circleci/',
        '.editorconfig',
        '.env.example.docker',
        '.env.example',
        '.env.testing',
        '.eslintrc.js',
        '.git/',
        '.gitattributes',
        '.gitignore',
        '.styleci.yml',
        '.stylelintrc',
        'docs',
        'LaravelREADME.md',
        'Makefile',
        'node_modules/',
        'package-lock.json',
        'package.json',
        'patches',
        'phpcs.xml',
        'phpunit.xml',
        'postcss.config.js',
        'README.md',
        'server.php',
        'storage/',
        'tests/',
        'traefik.yml',
        'webpack.mix.js',
    ],
    'exclude-file' => false,
    'include'      => [],
    'include-file' => false,
    'filter'       => [],
    'filter-file'  => false,
    'filter-perdir'=> false,
    'flags'        => 'rz', // Recursive, with compress
    'options'      => ['delete'],
    'timeout'      => 60,
]);

// Hosts
host('52.8.89.103')
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/datastore.hammer.ucla.edu')
    ->set('stage', 'production');

host('stage.datastore.hammer.cogapp.com')
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/stage.datastore.hammer.cogapp.com')
    ->set('stage', 'staging');

host('dev.datastore.hammer.cogapp.com')
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/dev.datastore.hammer.cogapp.com')
    ->set('stage', 'dev');

// Tasks
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:shared',
    'deploy:writable',
    'artisan:storage:link',
    'artisan:responsecache:clear',
    'artisan:view:clear',
    'artisan:cache:clear',
    'artisan:config:cache',
    'artisan:migrate',
    // publish is a group task which includes symlink, unlock, cleanup and success.
    'deploy:publish',
]);



// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');
