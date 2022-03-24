@extends('layouts.site')

@section('container')
    <div class="container my-5">
        @if ($url == 'dados')
            <div class="row justify-content-center">
                <div class="form-group col-12 text-center"><h2>Vamos confirma os seus dados.</h2></div>
                <div class="col-12 col-md-6">
                    <form action="{{url('atualizarPagamento')}}" method="post">
                        @csrf
                        <input type="hidden" name="type" value="{{$url}}">
                        <div class="row">
                            <div class="form-group col-12">
                                <label for="name">Nome Completo</label>
                                <input type="text" name="name" class="form-control form-control-sm" value="{{auth()->user()->name}}">
                            </div>
                            <div class="form-group col-12">
                                <label for="cnpj_cpf">CNPJ/CPF</label>
                                <input type="text" name="cnpj_cpf" class="form-control form-control-sm" value="{{auth()->user()->cnpj_cpf}}">
                            </div>
                            <div class="form-group col-12">
                                <label for="birth_date">Data de Nascimento</label>
                                <input type="text" name="birth_date" class="form-control form-control-sm" value="{{date('d/m/Y', strtotime(str_replace('-','/',auth()->user()->birth_date)))}}">
                            </div>
                            <div class="form-group col-12 text-center">
                                <button type="submit" class="btn btn-success">Salvar e Continuar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if ($url == 'enderecos')
            <div class="row">
                @foreach ($addresses as $address)
                    <div class="col-12 col-md-8 col-lg-4">
                        <h3 class="mb-3">Endereço</h3>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Cep: {{$address->post_code}}</li>
                            <li class="list-group-item">Cidade: {{$address->city}} - UF: {{$address->state}}</li>
                            <li class="list-group-item">Bairro: {{$address->address2}}</li>
                            <li class="list-group-item">{{$address->address}} - Nº {{$address->number}}</li>
                            <li class="list-group-item">Complemento: {{$address->complement}}</li>
                            <li class="list-group-item">Telefone: {{$address->phone1}}</li>
                            <li class="list-group-item">Celular: {{$address->phone2}}</li>
                            <li class="list-group-item">
                                <a href="#" class="btn btn-block btn-primary" data-toggle="modal" data-target="#enderecos" data-dados="{{json_encode($address)}}"><i class="fa fa-user-cog"></i> Alterar Endereço</a>
                                <form action="{{url('atualizarPagamento')}}" method="post" class="my-2">
                                    @csrf
                                    <input type="hidden" name="type" value="enderecos">
                                    <input type="hidden" name="address_id" value="{{$address->id}}">
                                    <button type="submit" class="btn btn-block btn-success">Selecionar Endereço <i class="fa fa-arrow-right"></i></button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endforeach

                @if ($addresses->count() <= 2)
                    <div class="col-12 col-md-8 col-lg-4"><a href="#" class="btn btn-block btn-primary" data-toggle="modal" data-target="#enderecos"><i class="fa fa-map-marker-alt"></i> Adicionar Novo Endereço</a></div>
                @endif
            </div>
        @endif

        @if ($url == 'transportes')
            <form action="{{url('atualizarPagamento')}}" method="post">
                @csrf
                <input type="hidden" name="type" value="transportes">
                <input type="hidden" name="preparation_time_final" value="{{$preparation_time_final}}" >
                <div class="row">
                    <div class="col-12 col-md-8 col-lg-4">
                        <h3 class="mb-3">Endereço Escolhido</h3>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Cep: {{$address->post_code}}</li>
                            <li class="list-group-item">Cidade: {{$address->city}} - UF: {{$address->state}}</li>
                            <li class="list-group-item">Bairro: {{$address->address2}}</li>
                            <li class="list-group-item">{{$address->address}} - Nº {{$address->number}}</li>
                            <li class="list-group-item">Complemento: {{$address->complement}}</li>
                            <li class="list-group-item">Telefone: {{$address->phone1}}</li>
                            <li class="list-group-item">Celular: {{$address->phone2}}</li>
                        </ul>
                    </div>

                    <div class="col-12 col-md-8 col-lg-4">
                        <div class="row">
                            @if ($preparation_time_final > 0)
                                <div class="col-12">
                                    <p>A produtos no seu carrinho que possui tempo de preparo e sera enviado todos os produtos quando estiverem prontos.</p>
                                    <p>o tempo geral é baseado no produto que tem mais tempo de preparo.</p>
                                    <p>Tempo Geral de preparo é de {{$preparation_time_final}} dias mais o tempo de entrega da transportadora que escolher.</p>
                                </div>
                            @endif
                                <div class="co-12"><h5>Valores de Fretes:</h5></div>
                            @foreach ($transportadoras as $key_trans => $transportadora)
                                <div class="form-group col-12">
                                    <input type="radio" name="transport" id="transport_{{$key_trans}}" value="{{$transportadora['transport']}}|{{$transportadora['price']}}|{{$transportadora['time']}}">
                                    <label for="transport_{{$key_trans}}">{{$transportadora['transport']}} - R$ {{number_format($transportadora['price'], 2, ',','')}}</label>
                                    <p>Tempo estimado para entrega: {{$transportadora['time']}} dias.</p>
                                </div>
                            @endforeach
                            <div class="co-12"><button type="submit" class="btn btn-block btn-success next-payment" disabled>Continuar para o Pagamento <i class="fa fa-arrow-right"></i></button></div>
                        </div>
                    </div>

                    <div class="col-12 col-md-8 col-lg-4">
                        <div class="row">
                            <div class="form-group col-12">
                                <label for="coupon">Cupom de desconto</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Codigo do Cupom">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary btn-add-coupon">Adcionar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <input type="hidden" class="sub_total" value="{{cart_show()->total}}">
                                <h5>Sub. Total: R$ {{number_format(cart_show()->original_value, 2, ',','')}}</h5>
                            </div>
                            <div class="col-12">
                                <h5>Desconto: R$ -{{number_format((cart_show()->original_value - cart_show()->total), 2, ',','')}}</h5>
                            </div>
                            <div class="col-12 coupon-d d-none">
                                <h5>Cupom: R$ -<span class="campo-valor-coupon"></span></h5>
                            </div>
                            <div class="col-12">
                                <h5>Frete: <span class="frete"></span></h5>
                            </div>
                            <div class="col-12">
                                <h5>Total: <span class="total">R$ {{number_format(cart_show()->total, 2, ',','')}}</span></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif

        @if ($url == 'finalizar')
            <input type="hidden" class="total" value="{{(session('transport')['price']+(session()->get('coupon') ? session()->get('coupon')['value'] : cart_show()->total))}}">
            <div class="row">
                <div class="col-12 col-lg-6">
                    <h3 class="mb-3">Dados e valores</h3>
                    {{-- Dados de entrega --}}
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Endereço de entrega:</strong></li>
                        <li class="list-group-item">{{$address->address}} - Nº {{$address->number}} - {{$address->address2}} / {{$address->complement}}</li>
                        <li class="list-group-item">{{$address->city}}/{{$address->state}} - {{$address->post_code}}</li>
                        <li class="list-group-item">{{$address->address}} - Nº {{$address->number}}</li>
                        <li class="list-group-item">Telefone: {{$address->phone1}} / Celular: {{$address->phone2}}</li>
                    </ul>
                    {{-- Transportadora --}}
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Transportadora: </strong>{{session('transport')['carrier_name']}}</li>
                    </ul>
                    {{-- Produtos --}}
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Produtos:</strong></li>
                        @foreach (cart_show()->content as $content)
                            <li class="list-group-item">{{$content->name}} - R$ {{number_format($content->price, 2, ',','')}} - {{$content->attributes->product_sales_unit}}</li>
                        @endforeach
                    </ul>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Sub. Total: </strong>R$ {{number_format(cart_show()->original_value, 2,',','')}}</li>
                        <li class="list-group-item"><strong>Desconto: </strong>R$ -{{number_format((cart_show()->original_value - cart_show()->total), 2,',','')}}</li>
                        @if (session()->get('coupon'))
                            <li class="list-group-item"><strong>Cupom </strong>R$ -{{number_format((session()->get('coupon')['desconto']), 2,',','')}}</li>
                        @endif
                        <li class="list-group-item"><strong>Frete: </strong>R$ {{number_format(session('transport')['price'], 2,',','')}}</li>
                        <li class="list-group-item"><strong>Total: </strong>R$ {{number_format((session('transport')['price']+(session()->get('coupon') ? session()->get('coupon')['value'] : cart_show()->total)), 2,',','')}}</li>
                    </ul>
                </div>

                <div class="col-12 col-lg-6">
                    <h4>Dados de Pagamento.</h4>
                    <ul class="nav nav-tabs" id="tabPayment" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="credit-tab" data-toggle="tab" href="#credit" role="tab" aria-controls="credit" aria-selected="true">Cartão de crédito</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="ticket-tab" data-toggle="tab" href="#ticket" role="tab" aria-controls="ticket" aria-selected="false">Boleto</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="pix-tab" data-toggle="tab" href="#pix" role="tab" aria-controls="pix" aria-selected="false">Pix</a>
                        </li>
                    </ul>

                    <div class="tab-content my-2" id="tabPaymentContent">
                        <div class="tab-pane fade show active" id="credit" role="tabpanel" aria-labelledby="credit-tab">
                            <form method="post" action="{{route('finalizarPagamento')}}">
                                @csrf
                                <input type="hidden" name="payment_method" value="credit_card">
                                {{-- Dados do cartão --}}
                                <div class="row justify-content-center">
                                    <div class="form-group col-6">
                                        <input type="checkbox" id="multiple_card" name="multiple_card" value="true">
                                        <label for="multiple_card">Pagar com dois cartões?</label>
                                    </div>
                                    <div class="form-group col-8 d-none">
                                        <label for="">Valor Parcial</label>
                                        <input type="text" class="form-control real" name="parcial_value" value="{{number_format((session('transport')['price']+(session()->get('coupon') ? session()->get('coupon')['value'] : cart_show()->total)), 2,',','')}}">
                                    </div>
                                </div>
                                <div class="row justify-content-center">
                                    <div class="form-group col-12">
                                        <label for="">Nome</label>
                                        <input placeholder="(como escrito no cartão)" class="form-control" type="text" name="card_holder_name"/>
                                    </div>
                                    <div class="form-group col-12">
                                        <label for="">Número do cartão</label>
                                        <input placeholder="Número do cartão" class="form-control card" type="text" name="card_number"/>
                                    </div>
                                    <div class="form-group col-4">
                                        <label for="">Mês de expiração</label>
                                        <input placeholder="(mm)" class="form-control expiration_month" type="text" name="card_expiration_month"/>
                                    </div>
                                    <div class="form-group col-4">
                                        <label for="">Ano de expiração</label>
                                        <input placeholder="(aaaa)" class="form-control expiration_year" type="text" name="card_expiration_year"/>
                                    </div>
                                    <div class="form-group col-4">
                                        <label for="">Código de segurança</label>
                                        <input placeholder="Código de segurança" class="form-control cvv" type="text" name="card_cvv" id="card_cvv"/>
                                    </div>
                                </div>

                                <div class="row justify-content-center">
                                    <div class="form-group col-8">
                                        <label for="">Parcelas</label>
                                        <select name="installments" class="form-control"></select>
                                    </div>
                                </div>

                                <div class="card-2 mt-2 d-none">
                                    <div class="row justify-content-center">
                                        <div class="col-9"><h4>Dados do Segundo Cartão</h4></div>
                                        <div class="form-group col-8">
                                            <label for="">Valor Parcial</label>
                                            <input type="text" class="form-control real" name="parcial_value_2" readonly value="0">
                                        </div>
                                    </div>
                                    <div class="row justify-content-center">
                                        <div class="form-group col-12">
                                            <label for="">Nome</label>
                                            <input placeholder="(como escrito no cartão)" class="form-control" type="text" name="card_holder_name_2"/>
                                        </div>
                                        <div class="form-group col-12">
                                            <label for="">Número do cartão</label>
                                            <input placeholder="Número do cartão" class="form-control card" type="text" name="card_number_2"/>
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="">Mês de expiração</label>
                                            <input placeholder="(mm)" class="form-control expiration_month" type="text" name="card_expiration_month_2"/>
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="">Ano de expiração</label>
                                            <input placeholder="(aaaa)" class="form-control expiration_year" type="text" name="card_expiration_year_2"/>
                                        </div>
                                        <div class="form-group col-4">
                                            <label for="">Código de segurança</label>
                                            <input placeholder="Código de segurança" class="form-control cvv" type="text" name="card_cvv_2" id="card_cvv_2"/>
                                        </div>
                                    </div>

                                    <div class="row justify-content-center">
                                        <div class="form-group col-8">
                                            <label for="">Parcelas</label>
                                            <select name="installments_2" class="form-control"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row justify-content-center">
                                    <div class="col-9 form-group mt-2">
                                        <button type="button" class="btn btn-primary btn-block btn-send-payment">Pagar</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane" id="ticket" role="tabpanel" aria-labelledby="ticket-tab">
                            <form method="post" action="{{route('finalizarPagamento')}}">
                                @csrf
                                <input type="hidden" name="payment_method" value="ticket">
                                <div class="row justify-content-center">
                                    <div class="col-9 form-group mt-2">
                                        <button type="button" class="btn btn-primary btn-block btn-send-payment">Gerar Boleto</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane" id="pix" role="tabpanel" aria-labelledby="pix-tab">
                            <form method="post" action="{{route('finalizarPagamento')}}">
                                @csrf
                                <input type="hidden" name="payment_method" value="pix">
                                <div class="row justify-content-center">
                                    <div class="col-9 form-group mt-2">
                                        <button type="button" class="btn btn-primary btn-block btn-send-payment">Gerar Pix</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal para regstrar endereços --}}
    @if ($url == 'enderecos')
        <div class="modal fade" id="enderecos">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{asset('enderecoSave')}}" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id">
                        <div class="modal-header">
                            <h4 class="modal-title">Endereço <div class="spinner-border d-none loadCep" role="status"><span class="sr-only">Loading...</span></div></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group col-5 col-md-4">
                                    <label for="post_code">CEP:</label>
                                    <input type="text" class="form-control @error('post_code') is-invalid @enderror" name="post_code" placeholder="00000-000">

                                    @error('post_code')
                                        <span class="invalid-feedback" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-7 col-md-8">
                                    <label for="address">Endereço:</label>
                                    <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" placeholder="Endereço/Rua/Avenida" >

                                    @error('address')
                                        <span class="invalid-feedback" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-3">
                                    <label for="number">Nª:</label>
                                    <input type="text" class="form-control @error('number') is-invalid @enderror" name="number" placeholder="0000">

                                    @error('number')
                                        <span class="invalid-feedback" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-9">
                                    <label for="complement">Complemento:</label>
                                    <input type="text" class="form-control" name="complement" placeholder="Complemento">
                                </div>

                                <div class="form-group col-12">
                                    <label for="address2">Bairro:</label>
                                    <input type="text" class="form-control @error('address2') is-invalid @enderror" name="address2" placeholder="Bairro">

                                    @error('address2')
                                        <span class="invalid-feedback" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-md-6">
                                    <label for="state">Estado</label>
                                    <select name="state" class="form-control select2 state @error('state') is-invalid @enderror"">
                                        <option value="">::Selecione uma Opção::</option>
                                    </select>

                                    @error('state')
                                        <span class="invalid-feedback" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-md-6">
                                    <label for="city">Cidade</label>
                                    <select name="city" class="form-control select2 city @error('city') is-invalid @enderror">
                                        <option value="">::Selecione uma Opção::</option>
                                    </select>

                                    @error('city')
                                        <span class="invalid-feedback" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group col-12">
                                    <label for="phone1">Telefone:</label>
                                    <input type="text" class="form-control" name="phone1" placeholder="Telefone">
                                </div>
                                <div class="form-group col-12">
                                    <label for="phone2">Celular:</label>
                                    <input type="text" class="form-control @error('phone2') is-invalid @enderror" name="phone2" placeholder="Celular">

                                    @error('phone2')
                                        <span class="invalid-feedback" role="alert">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Fechar</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('js')
    <script>
        $(document).ready(function(){
            if($('#enderecos').find('.is-invalid').length > 0) $('#enderecos').modal('show');

            @if($url == 'finalizar')
                $(document).on('click', '.btn-send-payment', function() {
                    var btn = $(this);
                    var btn_text = $(this).html();
                    $(this).html(`<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>`).prop('disabled', true);

                    $.ajax({
                        url: btn.closest('form').attr('action'),
                        type: 'POST',
                        data: $(this).closest('form').serialize(),
                        success: (data)=>{
                            console.log(data);
                            $(this).html(btn_text).prop('disabled', false);
                            window.location.href = data;
                        },
                        error: (err)=>{
                            console.log(err);
                            $(this).html(btn_text).prop('disabled', false);
                        }
                    });
                });
                var clear_timeout_installmnets = '';
                $('[name="parcial_value"]').on('keyup', function(){
                    clearTimeout(clear_timeout_installmnets);
                    var value_real = parseFloat($('.total').val()).toFixed(2);
                    $('[name="parcial_value_2"]').val((value_real-$(this).val().replace(',','.')).toString().replace('.',','));
                    clear_timeout_installmnets = setTimeout(() => {
                        installments('parcial_value', 'installments');
                        installments('parcial_value_2', 'installments_2');
                    }, 1000);
                });

                $(function(){
                    installments('parcial_value', 'installments');
                });
            @endif

            $('#multiple_card').on('change', function(){
                if($(this).prop('checked')){
                    $('[name="parcial_value"]').parent().removeClass('d-none');
                    $('.card-2').removeClass('d-none');
                }else{
                    $('[name="parcial_value"]').parent().addClass('d-none');
                    $('[name="parcial_value"]').val(parseFloat($('.total').val()).toFixed(2).replace('.',','));
                    $('.card-2').addClass('d-none');
                    $('[name="parcial_value_2"]').val(0);
                    installments('parcial_value', 'installments');
                }
            });


        });

        function installments(field_value, field_installments){
            $.ajax({
                    url: 'https://api.pagar.me/1/transactions/calculate_installments_amount',
                    type: 'GET',
                    data: {
                        amount: $('[name="'+field_value+'"]').val().replace(',',''),
                        api_key: '{{ENV("PAGARME_API_KEY")}}',
                        free_installments: '1',
                        interest_rate: '0',
                        max_installments: '12',
                    },
                    success: (data) => {
                        $('[name="'+field_installments+'"]').empty();
                        $.each(data.installments, (key, value) => {
                            $('[name="'+field_installments+'"]').append(`
                                <option value="${value.installment}">${value.installment} x R$${(value.installment_amount/100).toString().replace('.',',')} = R$${(value.amount/100).toString().replace('.',',')}</option>
                            `);
                        });
                    }
                });
        }
    </script>
@endsection
