<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockistToTerminalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stockist_to_terminals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('super_stockist_id')->references('id')->on('users')->onDelete('cascade');

            $table->integer('stockist_id')->nullable(true);
            $table->integer('terminal_id')->nullable(true);

//            $table->unique(['stockist_id', 'terminal_id']);

//            $table->foreignId('stockist_id')->references('id')->on('users')->onDelete('cascade');
//            $table->foreignId('terminal_id')->references('id')->on('users')->onDelete('cascade');
//            $table->unique(['stockist_id', 'terminal_id']);

            $table->tinyInteger('inforce')->default(1);
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
        Schema::dropIfExists('stockist_to_terminals');
    }
}
