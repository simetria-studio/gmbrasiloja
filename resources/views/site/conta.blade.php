@extends('layouts.site')

@section('container')
    <div class="container my-5">
        <div class="row">
            <div class="col-6 py-3 px-3 border-bottom">
                <div class="row">
                    <div class="col-4">
                        <div class="alert alert-warning" role="alert">
                            A receber: 
                                <br> {{print_valor_vindo_pagar_me($saldo_recebedor->waiting_funds->amount)}}
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="alert alert-success" role="alert">
                            Disponível: 
                                <br> {{print_valor_vindo_pagar_me($saldo_recebedor->available->amount)}}
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="alert alert-danger" role="alert">
                            Já transferidos:
                            <br> {{print_valor_vindo_pagar_me($saldo_recebedor->transferred->amount)}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-6 py-3 px-3 border-bottom">
                {!! printInformacaoBanco($recebedor->bank_account, 'bank_code', "Banco", function ($bankCode){
                    $nomeBanco = bancos()[$bankCode]??'Banco não listado';
                    return "{$bankCode} - {$nomeBanco}";
                }) !!}
            </div>
            <div class="col-6 py-3 px-3 border-bottom">
                {!! printInformacaoBanco($recebedor->bank_account, 'agencia', "Agência") !!}
            </div>
    
            <div class="col-6 py-3 px-3 border-bottom">
                {!! printInformacaoBanco($recebedor->bank_account, 'agencia_dv', "Agência DV") !!}
            </div>
    
            <div class="col-6 py-3 px-3 border-bottom">
                {!! printInformacaoBanco($recebedor->bank_account, 'conta', "Conta") !!}
            </div>
    
            <div class="col-6 py-3 px-3 border-bottom">
                {!! printInformacaoBanco($recebedor->bank_account, 'conta_dv', "Conta DV") !!}
            </div>
    
            <div class="col-6 py-3 px-3 border-bottom">
                {!! printInformacaoBanco($recebedor->bank_account, 'type', 'Tipo da conta', function ($valor){
                    return (\Illuminate\Support\Str::replace(
                        ['_'], [' '], $valor
                    ));
                }) !!}
            </div>
    
            <div class="col-6 py-3 px-3 border-bottom">
                {!! printInformacaoBanco($recebedor->bank_account, 'document_type', 'Tipo de documento') !!}
            </div>
    
            <div class="col-6 py-3 px-3 border-bottom">
                {!! printInformacaoBanco($recebedor->bank_account, 'document_number', 'Documento') !!}
            </div>
    
            <div class="col-6 py-3 px-3 border-bottom">
                {!! printInformacaoBanco($recebedor->bank_account, 'legal_name', 'Nome') !!}
            </div>
    
        </div>
    
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Extrato</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body pad table-responsive">
                <div class="container">
                    <div class="row">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <th>Transação</th>
                                <th>Data - Hora</th>
                                <th>Valor</th>
                                <th>Saldo anterior</th>
                                <th>Saldo atual</th>
                            </thead>
                            <tbody>
                                @forelse($historico_recebedor as $balance)
                                    <tr class="{{($balance->amount - $balance->fee) <= 0 ? 'text-danger':'text-success'}}">
                                        <td class="font-italic font-weight-bold">#{{$balance->id}}</td>
                                        <td>{{(new \Carbon\Carbon($balance->date_created))->format('d/m/Y - H:i:s')}}</td>
                                        <td>{{print_valor_vindo_pagar_me(($balance->amount - $balance->fee))}}</td>
                                        <td>{{print_valor_vindo_pagar_me($balance->balance_old_amount)}}</td>
                                        <td>{{print_valor_vindo_pagar_me($balance->balance_amount)}}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            Nenhuma movimentação até agora =(
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- <div class="row my-3 justify-content-center">
            <div class="col-12 col-md-8">
                <div class="row py-3 px-2 border border-dark rounded">
                    <div class="col-12 col-md-4 text-center">
                        <b>Saldo Disponivel:</b> <span class="text-success">R$ {{number_format($user->bank->balance_available, 2, ',', '.')}}</span>
                    </div>
                    <div class="col-12 col-md-4 text-center">
                        <b>Saldo Retirado:</b> <span class="text-danger">R$ {{number_format($user->bank->balance_withdrawn, 2, ',', '.')}}</span>
                    </div>
                    <div class="col-12 col-md-4 text-center">
                        <b>Total:</b> <span class="text-dark">R$ {{number_format($user->bank->accumulated_total, 2, ',', '.')}}</span>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- <div class="table-responsive my-3">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th colspan="3">Historico da Conta</th>
                    </tr>
                    <tr>
                        <th>Titulo</th>
                        <th>Descrição</th>
                        <th>Cupom</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($histories as $history)
                        <tr>
                            <td>{{$history->type}}</td>
                            <td>{{$history->history}}</td>
                            <td>{{$history->coupon}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div> --}}
    </div>
@endsection