<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send user activate Email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $username = $this->ask('What is your username?');
        $email = $this->ask('What is your email?');
        $password = $this->secret('What is your password?');

        $validator = Validator::make([
            'username' => $username,
            'email' => $email,
            'password' => $password
        ], [
            'username' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if($validator->fails()) {
            foreach($validator->getMessageBag()->getMessages() as $key => $error) {
                foreach($error as $key => $message) {
                    $this->warn($message);
                }
            }
            $this->info('Admin not created.');
        }
        else {
            $check['username'] = DB::table('users')
                ->where('username', '=', $username)
                ->where('username_hash', '=', sha1($username));

            $check['email'] = DB::table('users')
                ->where('email', '=', $email);

            if($check['username']->count() === 0) {
                if($check['email']->count() === 0) {
                    $role = DB::table('user_role')
                        ->where('name', '=', 'admin');

                    if($role->count() !== 0) {
                        $result = DB::table('users')
                            ->insert([
                                'username' => $username,
                                'username_hash' => sha1($username),
                                'password' => password_hash($password, PASSWORD_BCRYPT),
                                'email' => $email,
                                'RID' => $role->first()->id,
                                'active' => '1',
                                'email_verified_at' => DB::raw('CURRENT_TIMESTAMP')
                            ]);

                        if($result) {
                            $this->info('Admin created.');
                        }
                        else {
                            $this->warning('Upps something went wrong!');
                            $this->info('Admin not created.');
                        }
                    }
                    else {
                        $this->warning('Admin role doesnt exists!');
                        $this->info('Admin not created.');
                    }

                }
                else {
                    $this->warning('Admin with email already exists!');
                    $this->info('Admin not created.');
                }
            }
            else {
                $this->warning('Admin with username already exists!');
                $this->info('Admin not created.');
            }
        }
    }
}
