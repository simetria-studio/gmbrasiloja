@extends('layouts.painel')

@section('container')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Clientes do Sistema</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{asset('/admin')}}">Dashboard</a></li>
                        <li class="breadcrumb-item">Clientes</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        {{-- Header do Card --}}
                        <div class="card-header">
                            <h3 class="card-title">Contas de Usuarios</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        {{-- Corpo do Card --}}
                        <div class="card-body pad table-responsive">
                            <div class="container">
                                <div class="row">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#novoCliente"><i class="fas fa-plus"></i> Novo Cliente</button>
                                    </div>
                                </div>
                            </div>

                            <div class="container mt-2 table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nº</th>
                                            <th>Nome</th>
                                            <th>CNPJ/CPF</th>
                                            <th>Email</th>
                                            <th>Afiliado</th>
                                            <th>Endereços</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($accounts as $account)
                                            <tr class="tr-id-{{$account->id}}">
                                                <td>{{$account->id}}</td>
                                                <td>{{$account->name}}</td>
                                                <td>{{$account->cnpj_cpf}}</td>
                                                <td>{{$account->email}}</td>
                                                <td>
                                                    @if (isset($account->requestAfiliado))
                                                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#analiseAfiliado" data-dados="{{json_encode($account->requestAfiliado)}}"><i class="fas fa-user-tie"></i> Analisar</a>
                                                    @endif
                                                    @if ($account->recipient_id)
                                                        <span class="bg-success rounded px-2 py-1">{{$account->recipient_id}}</span>
                                                    @else
                                                        <span class="bg-info rounded px-2 py-1">Não Afiliado</span>
                                                    @endif
                                                </td>
                                                <td><a href="{{url('admin/cliente/enderecos', $account->id)}}" class="btn btn-info btn-sm">({{$account->adresses->count()}}) <i class="fas fa-eye"></i> Visualizar</a></td>
                                                <td>
                                                    <div class="btn-group" role="group" aria-label="">
                                                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#atualizarSenha" data-dados="{{json_encode($account)}}"><i class="fas fa-edit"></i> Trocar Senha</a>
                                                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#editarCliente" data-dados="{{json_encode($account)}}"><i class="fas fa-edit"></i> Editar Cliente</a>
                                                        <a href="#" class="btn btn-danger btn-sm btn-editar" data-toggle="modal" data-target="#excluirCliente" data-dados="{{json_encode($account)}}"><i class="fas fa-trash"></i> Apagar Conta</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <th colspan="8">{{$accounts->count()}} Contas</th>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="container mt-2">{{$accounts->links()}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="analiseAfiliado">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="#" method="post" id="postConfirmaAfiliado">
                    @csrf
                    <input type="hidden" name="user_id">
                    <div class="modal-header">
                        <h4 class="modal-title">Requisição para ser Afiliado</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-12">
                                <label for="bank_code_id">Banco</label>
                                <select id="bank_code_id" name="bank_code" class="form-control select2">
                                    {!! collect(bancos())->map(function ($bancoNome, $bancoCode){ return "<option value='{$bancoCode}'>{$bancoCode} - {$bancoNome}</option>";})->join(' ') !!}
                                </select>
                            </div>
                            <div class="form-group col-8">
                                <label for="store_name">Agência</label>
                                <input name="agencia" class="form-control form-control-sm">
                            </div>

                            <div class="form-group col-4">
                                <label for="agencia_dv_id">{{--Agência DV--}}Digito Verificador</label>
                                <input id="agencia_dv_id" name="agencia_dv" class="form-control form-control-sm">
                            </div>

                            <div class="form-group col-8">
                                <label for="conta_id">Conta</label>
                                <input id="conta_id" name="conta" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col-4">
                                <label for="conta_dv_id">{{--Conta DV--}}Digito Verificador</label>
                                <input id="conta_dv_id" name="conta_dv" class="form-control form-control-sm">
                            </div>

                            <div class="form-group col-12">
                                <label for="store_name">Tipo da conta</label>
                                <select name="type" class="form-control form-control-sm">
                                    <option value="conta_corrente">
                                        Conta corrente
                                    </option>
                                    <option value="conta_poupanca">
                                        Conta poupanca
                                    </option>
                                    <option value="conta_corrente_conjunta">
                                        Conta corrente conjunta
                                    </option>
                                    <option value="conta_poupanca_conjunta">
                                        Conta poupanca conjunta
                                    </option>
                                </select>
                            </div>

                            <div class="form-group col-12">
                                <label for="document_number_id">CPF/CNPJ atrelado à conta</label>
                                <input id="document_number_id" name="cnpj_cpf" class="form-control form-control-sm" value="{{auth()->user()->cnpj_cpf}}">
                            </div>

                            <div class="form-group col-12">
                                <label for="legal_name_id">Nome/Razão social do dono da conta</label>
                                <input id="legal_name_id" name="legal_name" class="form-control form-control-sm max-caracteres" data-max_caracteres="30">
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Fechar</button>
                        <button type="button" class="btn btn-success btn-salvar" data-update_table="S" data-save_target="#postConfirmaAfiliado" data-save_route="{{route('confirmaAfiliado')}}"><i class="fas fa-save"></i> Confirmar Solicitação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="novoCliente">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="#" method="post" id="postNovoCliente">
                    @csrf
                    <div class="modal-header">
                        <h4 class="modal-title">Novo Clientes</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-12">
                                <label for="name">Nome Completo</label>
                                <input type="text" name="name" class="form-control" placeholder="Nome do Usuario">
                            </div>
                            <div class="form-group col-12">
                                <label for="email">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Email da Conta">
                            </div>
                            <div class="form-group col-12">
                                <label for="cnpj_cpf">CNPJ/CPF</label>
                                <input type="text" name="cnpj_cpf" class="form-control" placeholder="CNPJ ou CPF">
                            </div>
                            <div class="form-group col-12">
                                <label for="password">Senha</label>
                                <input type="password" name="password" class="form-control" placeholder="Senha da Conta">
                            </div>
                            <div class="form-group col-12">
                                <label for="password_confirmation">Comfirma Senha</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Confirma a senha digitada">
                            </div>
                            <div class="form-group col-12">
                                <input type="checkbox" id="afiliadoCheck" name="afiliado_check" value="true">
                                <label for="afiliadoCheck">Afiliado?</label>
                            </div>
                        </div>

                        <div class="form-row form-afiliado d-none">
                            <div class="form-group col-12">
                                <label for="bank_code_id_new">Banco</label>
                                <select name="bank_code" class="form-control select2">
                                    {!! collect(bancos())->map(function ($bancoNome, $bancoCode){ return "<option value='{$bancoCode}'>{$bancoCode} - {$bancoNome}</option>";})->join(' ') !!}
                                </select>
                            </div>
                            <div class="form-group col-8">
                                <label for="store_name">Agência</label>
                                <input name="agencia" class="form-control form-control-sm">
                            </div>

                            <div class="form-group col-4">
                                <label for="agencia_dv_id">{{--Agência DV--}}Digito Verificador</label>
                                <input  name="agencia_dv" class="form-control form-control-sm">
                            </div>

                            <div class="form-group col-8">
                                <label for="conta_id">Conta</label>
                                <input name="conta" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col-4">
                                <label for="conta_dv_id">{{--Conta DV--}}Digito Verificador</label>
                                <input name="conta_dv" class="form-control form-control-sm">
                            </div>

                            <div class="form-group col-12">
                                <label for="store_name">Tipo da conta</label>
                                <select name="type" class="form-control form-control-sm">
                                    <option value="conta_corrente">
                                        Conta corrente
                                    </option>
                                    <option value="conta_poupanca">
                                        Conta poupanca
                                    </option>
                                    <option value="conta_corrente_conjunta">
                                        Conta corrente conjunta
                                    </option>
                                    <option value="conta_poupanca_conjunta">
                                        Conta poupanca conjunta
                                    </option>
                                </select>
                            </div>

                            <div class="form-group col-12">
                                <label for="document_number_id">CPF/CNPJ atrelado à conta</label>
                                <input name="cnpj_cpf" class="form-control form-control-sm" value="{{auth()->user()->cnpj_cpf}}">
                            </div>

                            <div class="form-group col-12">
                                <label for="legal_name_id">Nome/Razão social do dono da conta</label>
                                <input name="legal_name" class="form-control form-control-sm max-caracteres" data-max_caracteres="30">
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Fechar</button>
                        <button type="button" class="btn btn-success btn-salvar" data-update_table="S" data-save_target="#postNovoCliente" data-save_route="{{route('novoCliente')}}"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editarCliente">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="#" method="post" id="postEditarCliente">
                    @csrf
                    <input type="hidden" name="id">
                    <div class="modal-header">
                        <h4 class="modal-title">Atualizar Cliente</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-12">
                                <label for="name">Nome Completo</label>
                                <input type="text" name="name" class="form-control name" placeholder="Nome do Usuario">
                            </div>
                            <div class="form-group col-12">
                                <label for="email">Email</label>
                                <input type="email" name="email" class="form-control name" placeholder="Email da Conta">
                            </div>
                            <div class="form-group col-12">
                                <label for="cnpj_cpf">CNPJ/CPF</label>
                                <input type="text" name="cnpj_cpf" class="form-control" placeholder="CNPJ ou CPF">
                            </div>
                            <div class="form-group col-12">
                                <input type="checkbox" id="afiliadoCheck_edit" name="afiliado_check" value="true">
                                <label for="afiliadoCheck_edit">Afiliado?</label>
                            </div>
                        </div>

                        <div class="form-row my-3 form-afiliado d-none">
                            <div class="form-group col-12">
                                <label for="bank_code_id_new">Banco</label>
                                <select name="bank_code" class="form-control select2">
                                    {!! collect(bancos())->map(function ($bancoNome, $bancoCode){ return "<option value='{$bancoCode}'>{$bancoCode} - {$bancoNome}</option>";})->join(' ') !!}
                                </select>
                            </div>
                            <div class="form-group col-8">
                                <label for="store_name">Agência</label>
                                <input name="agencia" class="form-control form-control-sm">
                            </div>

                            <div class="form-group col-4">
                                <label for="agencia_dv_id">{{--Agência DV--}}Digito Verificador</label>
                                <input  name="agencia_dv" class="form-control form-control-sm">
                            </div>

                            <div class="form-group col-8">
                                <label for="conta_id">Conta</label>
                                <input name="conta" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col-4">
                                <label for="conta_dv_id">{{--Conta DV--}}Digito Verificador</label>
                                <input name="conta_dv" class="form-control form-control-sm">
                            </div>

                            <div class="form-group col-12">
                                <label for="store_name">Tipo da conta</label>
                                <select name="type" class="form-control form-control-sm">
                                    <option value="conta_corrente">
                                        Conta corrente
                                    </option>
                                    <option value="conta_poupanca">
                                        Conta poupanca
                                    </option>
                                    <option value="conta_corrente_conjunta">
                                        Conta corrente conjunta
                                    </option>
                                    <option value="conta_poupanca_conjunta">
                                        Conta poupanca conjunta
                                    </option>
                                </select>
                            </div>

                            <div class="form-group col-12">
                                <label for="document_number_id">CPF/CNPJ atrelado à conta</label>
                                <input name="cnpj_cpf" class="form-control form-control-sm" value="{{auth()->user()->cnpj_cpf}}">
                            </div>

                            <div class="form-group col-12">
                                <label for="legal_name_id">Nome/Razão social do dono da conta</label>
                                <input name="legal_name" class="form-control form-control-sm max-caracteres" data-max_caracteres="30">
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Fechar</button>
                        <button type="button" class="btn btn-success btn-salvar" data-update_table="S" data-save_target="#postEditarCliente" data-save_route="{{route('atualizarCliente')}}"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="excluirCliente">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="#" method="post" id="postExcluirCliente">
                    @csrf
                    <input type="hidden" name="id">
                    <div class="modal-header">
                        <h4 class="modal-title">Conta de(a) <span class="_name"></span></h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h5>Tem certeza que gostaria de apagar essa conta?</h5>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Fechar</button>
                        <button type="button" class="btn btn-danger btn-salvar" data-trash="S" data-save_target="#postExcluirCliente" data-save_route="{{route('excluirCliente')}}"><i class="fas fa-trash"></i> Excluir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="atualizarSenha">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="#" method="post" id="postAtualizarSenha">
                    @csrf
                    <input type="hidden" name="id">
                    <div class="modal-header">
                        <h4 class="modal-title">Atualizar senha de(a) <span class="_name"></span></h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-12">
                                <label for="password">Nova Senha</label>
                                <input type="password" name="password" class="form-control" placeholder="Nova Senha">
                            </div>
                            <div class="form-group col-12">
                                <label for="password_confirmation">Confirma Nova Senha</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Confirmar Nova Senha">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Fechar</button>
                        <button type="button" class="btn btn-success btn-salvar" data-save_target="#postAtualizarSenha" data-save_route="{{route('atualizarSenhaCliente')}}"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection