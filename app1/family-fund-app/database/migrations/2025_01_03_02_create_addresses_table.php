<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->foreignId('person_id')->constrained('persons')->onDelete('cascade');
            $table->enum('type', ['home', 'work', 'other'])->default('home');
            $table->boolean('is_primary')->default(false);
            $table->string('street');
            $table->string('number');
            $table->string('complement')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('zip_code');
            $table->string('country')->default('Brazil');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
}; 