<?php

namespace Deployer;

desc('Execute artisan responsecache:clear');
task('artisan:responsecache:clear', function () {
    cd('{{release_path}}');
    run('php artisan responsecache:clear');
});
