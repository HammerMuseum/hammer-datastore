<?php

namespace App\Console\Commands;

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Console\Command;

class RegisterUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register an internal user.';

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
        $name = $this->ask('Enter username');
        $email = $this->ask('Enter email address');
        $password = $this->ask('Enter password');
        $confirmPassword = $this->ask('Confirm password');

        $comparePasswords = $this->comparePasswords($password, $confirmPassword);

        $registerController = new RegisterController();
        try {
            $user = $registerController->register(
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => $comparePasswords['password'],
                    'password_confirmation' => $comparePasswords['confirm_password'],
                ]
            );
            $this->info('User created successfully');
            $this->info('Your API token is: ' . $user['api_token']);
        } catch (\Exception $e) {
            $this->error('Unable to create user: ' . $e->getMessage());
        }
    }

    /**
     * Check that the entered passwords match
     *
     * @param $password
     * @param $confirmPassword
     * @return array
     */
    public function comparePasswords($password, $confirmPassword)
    {
        if ($password !== $confirmPassword) {
            $password = $this->ask('Passwords don\'t match. Enter password again.');
            $confirmPassword = $this->ask('Confirm password');
            $this->comparePasswords($password, $confirmPassword);
        }
        return [
            'password' => $password,
            'confirm_password' => $confirmPassword
        ];
    }
}
