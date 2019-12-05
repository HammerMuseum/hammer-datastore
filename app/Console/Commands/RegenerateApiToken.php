<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class RegenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh an API token';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $email = $this->ask('Enter the email address associated with the token.');
        /** @var User $user */
        $user = User::where('email', $email)->get()->first();
        if (!is_null($user) && isset($user->email)) {
            $user->generateApiToken();
            $this->info('Your new API token is: ' . $user->api_token);
        } else {
            $this->error('Unable to find user with email ' . $email);
        }
    }
}
