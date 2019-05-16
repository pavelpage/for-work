<?php

use App\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        $user = new User([
            'name' => 'Tester',
            'email' => 'test@test.ru',
            'password' => bcrypt('123456'),
            'remember_token' => str_random(10),
            'api_token' => '$2y$10$RjMwN8wdCJXNoXTRsKJK0.pJSAR/fcTUhDqqsNQ8Da5Ob6DUrb6X2', // bcrypt('token')
        ]);
        $user->save();
    }
}
