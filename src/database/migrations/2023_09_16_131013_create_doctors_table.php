<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_department');
            $table->unsignedBigInteger('id_hospital');
            $table->string('email')->unique();
            $table->string('username')->unique()->nullable();
            $table->string('password');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('experience')->nullable();
            $table->string('avatar')->nullable();
            $table->integer('gender')->nullable();
            $table->boolean('is_accept');
            $table->integer('search_number')->nullable();
            $table->string('role')->default('doctor');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('id_hospital')->references('id')->on('hospitals')->onDelete('cascade');
            $table->foreign('id_department')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctors');
    }
};
