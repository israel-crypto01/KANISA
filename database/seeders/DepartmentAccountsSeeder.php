<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DepartmentAccountsSeeder extends Seeder
{
    /**
     * One leader (kiongozi) account per department. Created only if missing, so
     * running this on every deploy never resets a changed password.
     */
    public function run(): void
    {
        // Make sure the default departments exist before attaching leaders to them.
        Department::ensureDefaultsExist();

        $accounts = [
            ['dept' => 'Idara ya Wamama (WWI)',            'name' => 'KIONGOZI WAMAMA (WWI)',  'email' => 'wamama@philadelphia', 'password' => 'wamama123', 'member_id' => 'EAGT-KBP-WAMAMA', 'gender' => 'female'],
            ['dept' => 'Idara ya Wababa',                  'name' => 'KIONGOZI WABABA',        'email' => 'wababa@philadelphia', 'password' => 'wababa123', 'member_id' => 'EAGT-KBP-WABABA', 'gender' => 'male'],
            ['dept' => 'Idara ya Vijana (CGM)',            'name' => 'KIONGOZI VIJANA (CGM)',  'email' => 'vijana@philadelphia', 'password' => 'vijana123', 'member_id' => 'EAGT-KBP-VIJANA', 'gender' => 'male'],
            ['dept' => 'Idara ya Watoto (CWI)',            'name' => 'KIONGOZI WATOTO (CWI)',  'email' => 'watoto@philadelphia', 'password' => 'watoto123', 'member_id' => 'EAGT-KBP-WATOTO', 'gender' => 'female'],
            ['dept' => 'Idara ya Kusifu na Kuabudu (KUSKI)', 'name' => 'KIONGOZI KUSKI',      'email' => 'kwaya@philadelphia',  'password' => 'kwaya123',  'member_id' => 'EAGT-KBP-KWAYA',  'gender' => 'male'],
        ];

        foreach ($accounts as $account) {
            $department = Department::where('name_sw', $account['dept'])->first();
            if (! $department) {
                continue; // DepartmentSeeder has not created it (yet).
            }

            $user = User::firstOrCreate(['email' => $account['email']], [
                'name'      => $account['name'],
                'password'  => Hash::make($account['password']),
                'member_id' => $account['member_id'],
                'role'      => 'kiongozi',
                'status'    => 'active',
                'gender'    => $account['gender'],
                'cheo'      => 'Mwenyekiti',
            ]);

            $user->departments()->syncWithoutDetaching([
                $department->id => ['role' => 'kiongozi', 'cheo' => 'Mwenyekiti'],
            ]);
        }
    }
}
