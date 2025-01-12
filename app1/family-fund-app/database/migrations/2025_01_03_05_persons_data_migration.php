<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Person;
use App\Models\Account;
use App\Models\AccountContactPerson;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up()
    {
        // migrate existing users to persons
        User::all()->each(function ($user) {
            $person = Person::where('email', $user->email)->get();
            $person = $person->first();
            if (!$person) {
                $person = Person::create([
                    'first_name' => substr($user->name, 0, strpos($user->name, ' ')),
                    'last_name' => substr($user->name, strpos($user->name, ' ') + 1),
                    'email' => $user->email,
                    'birthday' => null,
                ]);
                $person->save();
            } else {
                Log::info('A Person already exists for user ' . $user->email);
            }
            $user->person_id = $person->id;
            $user->save();
        });

        // migrate existing accounts to persons
        Account::all()->each(function ($account) {
            // split email_cc by commas
            $emails = explode(',', $account->email_cc);
            foreach ($emails as $email) {
                // try to find existing person using account email_cc
                $person = Person::where('email', '=', $email)->get();
                Log::info('A Person: ' . $person);
                $person = $person->first();
                if (!$person) {
                    Log::info('Creating person for account ' . $account->id . ' with email ' . $email);
                    $user = $account->user()->first();
                    if (!$user) {
                        Log::info('B Account user not found');
                        continue;
                    }
                    Log::info('B Account user: ' . $user);
                    $person = Person::create([
                        'first_name' => $user->name,
                        'last_name' => '',
                        'email' => $email,
                        'birthday' => null,
                    ]);
                    Log::info('B Person: ' . $person);
                    $person->save();
                } else {
                    Log::info('Person already exists for account ' . $account->id . ' with email ' . $email);
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
        // run in a transaction
        AccountContactPerson::truncate();
        $persons = Person::all();
        foreach ($persons as $person) {
            $person->delete();
        }
    }
}; 