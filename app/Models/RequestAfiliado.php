<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestAfiliado extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bank_code',
        'agencia',
        'agencia_dv',
        'conta',
        'conta_dv',
        'type',
        'cnpj_cpf',
        'legal_name',
    ];
}
