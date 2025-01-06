<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Person;
use App\Models\Account;
use App\Models\AccountContactPerson;

return new class extends Migration
{
    public function up()
    {
        // migrate existing users to persons
        User::all()->each(function ($user) {
            $person = Person::create([
                'first_name' => substr($user->name, 0, strpos($user->name, ' ')),
                'last_name' => substr($user->name, strpos($user->name, ' ') + 1),
                'email' => $user->email,
                'birthday' => null,
            ]);
            $user->person_id = $person->id;
            $user->save();
        });

        // migrate existing accounts to persons
        Account::all()->each(function ($account) {
            // split email_cc by commas
            $emails = explode(',', $account->email_cc);
            foreach ($emails as $email) {
                // try to find existing person using account email_cc
                $person = Person::where('email', '=', $email)->first();
                if (!$person) {
                    $person = Person::create([
                        'first_name' => $account->user->name,
                        'last_name' => '',
                        'email' => $account->user->email,
                        'birthday' => null,
                    ]);
                    $person->save();
                }
                $accountContactPerson = AccountContactPerson::create([
                    'account_id' => $account->id,
                    'person_id' => $person->id,
                ]);
                $accountContactPerson->save();
            }
        });
    }

    public function down()
    {
        // delete all persons
        Person::truncate();
        // delete all account_contact_persons
        AccountContactPerson::truncate();
    }
}; 