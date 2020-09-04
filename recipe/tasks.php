<?php

namespace Deployer;

desc('Execute artisan responsecache:clear');
task('artisan:responsecache:clear', artisan('responsecache:clear'));
