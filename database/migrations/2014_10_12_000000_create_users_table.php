<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('pin')->nullable();
            $table->string('dob')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('email_verify_code')->nullable();
            $table->string('email_verify_status')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('expires_at')->nullable();
            $table->string('balance')->nullable();
            $table->string('transfer_type')->nullable();
            $table->string('int_acc_number')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
