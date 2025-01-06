<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Person;
use App\Models\Account;

return new class extends Migration
{
    public function up()
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->date('birthday')->nullable();
            $table->foreignId('legal_guardian_id')->nullable()->constrained('persons');
            $table->timestamps();
        });

        // Add person_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->constrained('persons');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropColumn('person_id');
        });
        Schema::dropIfExists('persons');
    }
}; 