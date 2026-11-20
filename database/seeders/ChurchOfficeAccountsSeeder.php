<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ChurchOfficeAccountsSeeder extends Seeder
{
    /**
     * Default accounts. Created only if missing, so re-running this seeder
     * (e.g. on every deploy) never resets a password that has been changed.
     */
    public function run(): void
    {
        $accounts = [
            ['name'=>'CHURCH SECRETARY','email'=>'katibu@philadelphia','password'=>'katibu123','member_id'=>'EAGT-KBP-SECRETARY','gender'=>'female','cheo'=>'KATIBU','user_role'=>'mshirika','role'=>'secretary'],
            ['name'=>'CHURCH DEACON','email'=>'shemasi@philadelphia','password'=>'shemasi123','member_id'=>'EAGT-KBP-DEACON','gender'=>'male','cheo'=>'SHEMASI','user_role'=>'mshirika','role'=>'deacon'],
            ['name'=>'CHURCH ACCOUNTANT','email'=>'mhasibu@philadelphia','password'=>'mhasibu123','member_id'=>'EAGT-KBP-ACCOUNTANT','gender'=>'male','cheo'=>'MHASIBU','user_role'=>'mshirika','role'=>'accountant'],
            ['name'=>'CHURCH ADMIN','email'=>'admin@philadelphia','password'=>'password','member_id'=>'EAGT-KBP-ADMIN','gender'=>'male','cheo'=>'ADMIN','user_role'=>'admin','role'=>'admin'],
            ['name'=>'MCHUNGAJI','email'=>'mchungaji@philadelphia','password'=>'password','member_id'=>'EAGT-KBP-MCHUNGAJI','gender'=>'male','cheo'=>'MCHUNGAJI','user_role'=>'mchungaji','role'=>'mchungaji'],
        ];

        foreach ($accounts as $account) {
            $role = Role::firstOrCreate(['name' => $account['role']]);

            $user = User::firstOrCreate(['email' => $account['email']], [
                'name'      => $account['name'],
                'password'  => Hash::make($account['password']),
                'member_id' => $account['member_id'],
                'role'      => $account['user_role'],
                'status'    => 'active',
                'gender'    => $account['gender'],
                'cheo'      => $account['cheo'],
            ]);

            if (! $user->hasRole($role->name)) {
                $user->syncRoles([$role]);
            }
        }
    }
}
