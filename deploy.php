<?php

namespace Deployer;

require 'contrib/rsync.php';
require 'recipe/laravel.php';
require 'recipe/tasks.php';

// Project name.
set('application', 'Hammer Datastore');
set('repository', 'git@github.com:HammerMuseum/hammer-datastore.git');
set('git_tty', true);
set('allow_anonymous_stats', false);
set('http_user', 'www-data');

// Shared files/dirs between deploys.
add('shared_files', ['.env']);
add('shared_dirs', ['storage']);

// Writable dirs by web server.
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

// Rsync configuration.
set('rsync_src', __DIR__);
set('rsync_dest', '{{release_path}}');
set('rsync', [
    'exclude'      => [
        'deploy.php',
        '.ddev',
        '.git',
        'node_modules',
        '.babelrc',
        '.circleci/',
        '.editorconfig',
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

// Get host from CI environment variable
$dev_host = getenv('DEV_DEPLOY_URL');
$staging_host = getenv('STAGING_DEPLOY_URL');
$prod_host = getenv('PROD_DEPLOY_URL');

// If no hosts set, abort deployment with a instructions.
if (empty($dev_host) && empty($staging_host) && empty($prod_host)) {
    echo "No hosts set. Please set one of the following environment variables:\n";
    echo "- DEV_DEPLOY_URL \n- STAGING_DEPLOY_URL \n- PROD_DEPLOY_URL.\n";
    die();
}

// Hosts
host('prod')
    ->set('hostname', $prod_host)
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/datastore.hammer.ucla.edu')
    ->set('stage', 'production');

host('staging')
    ->set('hostname', $staging_host)
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/' . $staging_host)
    ->set('stage', 'staging');

host('dev')
    ->set('hostname', $dev_host)
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/' . $dev_host)
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
    // publish is a group task which includes migrate, symlink, unlock, cleanup and success.
    'deploy:publish',
]);

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.
before('deploy:symlink', 'artisan:migrate');
