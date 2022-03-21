<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestAfiliadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_afiliados', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('bank_code')->nullable();
            $table->string('agencia')->nullable();
            $table->string('agencia_dv')->nullable();
            $table->string('conta')->nullable();
            $table->string('conta_dv')->nullable();
            $table->string('type')->nullable();
            $table->string('cnpj_cpf')->nullable();
            $table->string('legal_name')->nullable();
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
        Schema::dropIfExists('request_afiliados');
    }
}
