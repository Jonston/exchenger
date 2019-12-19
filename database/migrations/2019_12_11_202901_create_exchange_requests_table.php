<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_requests', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->integer('user_from');
            $table->integer('user_to')->nullable();
            $table->enum('currency', ['stb', 'gnr']);
            $table->enum('operation', ['purchase', 'sale']);
            $table->float('amount')->unsigned();
            $table->float('rate')->unsigned();
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
        Schema::dropIfExists('exchange_requests');
    }
}
