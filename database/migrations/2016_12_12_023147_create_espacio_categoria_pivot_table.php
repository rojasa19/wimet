<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEspacioCategoriaPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('categoria_espacio')) {
            Schema::create('categoria_espacio', function (Blueprint $table) {
                $table->integer('categoria_id')->unsigned()->index();
                $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('cascade');
                $table->integer('espacio_id')->unsigned()->index();
                $table->foreign('espacio_id')->references('id')->on('espacios')->onDelete('cascade');
                $table->primary(['categoria_id', 'espacio_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categoria_espacio');
    }
}
