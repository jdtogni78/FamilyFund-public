<?php

namespace App\Models;

use App\Models\Person;

class PersonExt extends Person
{
    public static function legalGuardiansMap()
    {
        $map = [];
        $legalGuardians = Person::where('legal_guardian_id', '=', null)->get();
        foreach ($legalGuardians as $legalGuardian) {
            $map[$legalGuardian->id] = $legalGuardian->first_name . ' ' . $legalGuardian->last_name;
        }
        $map[null] = 'Select one';
        return $map;
    }
}
