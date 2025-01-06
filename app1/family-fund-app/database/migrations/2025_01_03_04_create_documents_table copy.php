<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('iddocuments', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->foreignId('person_id')->constrained('persons')->onDelete('cascade');
            $table->enum('type', ['CPF', 'RG', 'CNH', 'Passport', 'SSN', 'other']);
            $table->string('number');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('iddocuments');
    }
}; 