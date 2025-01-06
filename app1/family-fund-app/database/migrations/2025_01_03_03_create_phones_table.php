<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phones', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->foreignId('person_id')->constrained('persons')->onDelete('cascade');
            $table->string('number');
            $table->enum('type', ['mobile', 'home', 'work', 'other']);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('phones');
    }
}; 