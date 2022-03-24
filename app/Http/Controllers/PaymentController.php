<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Mail\Orders;
use App\Models\User;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Address;
use App\Models\Product;
use App\Mail\OrderPayment;
use App\Models\UsedCoupon;
use Illuminate\Support\Str;
use App\Models\OrderProduct;
use App\Models\PaymentOrder;

use Illuminate\Http\Request;
use App\Models\AffiliateBank;
use App\Models\CouponHistory;
use App\Models\ShippingCustomer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    public $order_number;

    public function finalizarPagamento(Request $request)
    {
        // \Log::info($request->all());
        // session()->forget('order_number');
        // session()->forget('address_id');
        // session()->forget('transport');
        // session()->forget('coupon');
        // dd('morto');
        $address_id = session('address_id');
        $address = Address::where('id', $address_id)->first();
        $transport = session('transport');

        if (empty(session()->get('order_number'))) {
        // if (true) {
            $order_number = Order::max('order_number');
            $this->order_number = $order_number = str_pad(($order_number + 1), 8, "0", STR_PAD_LEFT);
            session(['order_number' => $order_number]);

            // Criando o pedido
            $order = Order::create([
                'order_number' => $order_number,
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_email' => auth()->user()->email,
                'user_cnpj_cpf' => auth()->user()->cnpj_cpf,
                'birth_date' => auth()->user()->birth_date,
                'total_value' => ((session()->get('coupon') ? session()->get('coupon')['value'] : cart_show()->total) + $transport['price']),
                'cost_freight' => $transport['price'],
                'product_value' => cart_show()->total,
                'discount' => (cart_show()->original_value - cart_show()->total),
                'coupon_value' => session()->get('coupon')['desconto'] ?? null,
                'coupon' => session()->get('coupon')['coupon'] ?? null,
                'pay' => 0
            ]);

            if (session()->get('coupon')) {
                $coupon = Coupon::where('code', session()->get('coupon')['coupon'])->first();
                UsedCoupon::create([
                    'order_id' => $order_number,
                    'name' => $coupon->name,
                    'coupon' => $coupon->code,
                    'discount' => ($coupon->discount_type == 'P' ? 'R$ ' : '% ') . $coupon->value,
                    'start_date' => $coupon->start_date,
                    'final_date' => $coupon->final_date
                ]);
            }

            // Criando os produtos do pedido
            foreach (cart_show()->content as $content) {
                $product = Product::where('id', $content->attributes->product_id)->first();
                $sequence_order = OrderProduct::where('order_number', $order_number)->max('sequence');
                $sequence_order = ($sequence_order + 1);

                $discount = 0;
                if ($content->attributes->product_promotion == 'S') {
                    $product_value = (float)$content->attributes->product_value;
                    $project_meters = (float)$content->attributes->project_meters;
                    $project_value = (float)$content->attributes->project_value;
                    $product_p_value = (float)$content->attributes->product_p_value;
                    $product_p_porcent = (float)$content->attributes->product_p_porcent;

                    $originalValue = $project_meters !== 0 ? (($product_value * $project_meters) + ($content->price - $project_value)) : ($product_value + ($content->price - $product_p_value));
                    $discount = $originalValue - $content->price;
                }

                $order_product = OrderProduct::create([
                    'order_number' => $order_number,
                    'sequence' => $sequence_order,
                    'product_id' => $content->attributes->product_id,
                    'product_code' => $product->code,
                    'product_name' => $content->name,
                    'product_price' => $content->price,
                    'quantity' => $content->quantity,
                    'has_preparation' => $content->attributes->has_preparation,
                    'preparation_time' => $content->attributes->preparation_time,
                    'product_weight' => $content->attributes->product_weight,
                    'product_height' => $content->attributes->product_height,
                    'product_width' => $content->attributes->product_width,
                    'product_length' => $content->attributes->product_length,
                    'product_sales_unit' => $content->attributes->product_sales_unit,
                    'project_value' => $content->attributes->project_value,
                    'project_width' => $content->attributes->project_width,
                    'project_height' => $content->attributes->project_height,
                    'project_meters' => $content->attributes->project_meters,
                    'attributes' => $content->attributes->attributes_aux,
                    'project' => $content->attributes->project,
                    'discount' => $discount,
                    'note' => $content->attributes->note,
                ]);
            }
            \Log::info($address);
            // Criando os dados da entrega
            $shipping_customer = ShippingCustomer::create([
                'order_number' => $order_number,
                'post_code' => $address->post_code,
                'state' => $address->state,
                'city' => $address->city,
                'address2' => $address->address2,
                'address' => $address->address,
                'number' => $address->number,
                'complement' => $address->complement,
                'phone1' => $address->phone1,
                'phone2' => $address->phone2,
                'transport' => $transport['carrier_name'],
                'price' => $transport['price'],
                'time' => $transport['time'],
            ]);
        } else {
            $this->order_number = $order_number = session()->get('order_number');
        }

        $first_card = true;
        if (isset($request->multiple_card)) {
            if (str_replace(['.', ','], ['', '.'], $request->parcial_value) < str_replace(['.', ','], ['', '.'], $request->parcial_value_2)) $first_card = false;
            $payment = $this->pagarMe($request, $first_card);
            $first_card = true;
            if (str_replace(['.', ','], ['', '.'], $request->parcial_value) > str_replace(['.', ','], ['', '.'], $request->parcial_value_2)) $first_card = false;
            $request->parcial_value         = $request->parcial_value_2;
            $request->card_holder_name      = $request->card_holder_name_2;
            $request->card_number           = $request->card_number_2;
            $request->card_expiration_month = $request->card_expiration_month_2;
            $request->card_expiration_year  = $request->card_expiration_year_2;
            $request->card_cvv              = $request->card_cvv_2;
            $request->installments          = $request->installments_2;
            $payment = $this->pagarMe($request, $first_card);
        } else {
            $payment = $this->pagarMe($request, $first_card);
        }

        if (session()->get('coupon')) {
            $coupon = Coupon::where('code', session()->get('coupon')['coupon'])->first();
            foreach (json_decode($coupon->user_id) as $user_id) {
                CouponHistory::create([
                    'user_id' => $user_id,
                    'type' => 'Recebimento de Venda',
                    'history' => 'Recebimento de venda feito site',
                    'coupon' => $coupon->code
                ]);
            }
        }

        // Envio de emails
        Mail::to(auth()->user()->email)->send(new Orders($order_number, 'comprador'));
        Mail::to('suportegmbrasilvd@gmail.com')->send(new Orders($order_number, 'vendedor'));

        session()->forget('order_number');
        session()->forget('address_id');
        session()->forget('transport');
        session()->forget('coupon');
        \Cart::clear();

        return response()->json(route('perfil.meusPedidos') . '?order_number=' . $this->order_number, 200);
    }

    public function notificaPagamento(Request $request)
    {
        \Log::info($request->all());
        if ($request->current_status == 'paid') {
            $payment = PaymentOrder::where('payment_id', $request->id)->first();
            Order::where('order_number', $payment->order_number)->update(['pay' => 1]);
            $orders = Order::where('order_number', $payment->order_number)->with(['coupon', 'orderProducts', 'shippingCustomer'])->first();

            if (!empty($orders->user_email)) {
                Mail::to($orders->user_email)->send(new OrderPayment($orders, 'paid', 'comprador'));
            }
        }
        if ($request->current_status == 'canceled' || $request->current_status == 'refused') {
            $payment = PaymentOrder::where('payment_id', $request->id)->first();
            Order::where('order_number', $payment->order_number)->update(['pay' => 3]);
            $orders = Order::where('order_number', $payment->order_number)->with(['coupon', 'orderProducts', 'shippingCustomer'])->first();

            if (!empty($orders->user_email)) {
                Mail::to($orders->user_email)->send(new OrderPayment($orders, 'canceled', 'comprador'));
                Mail::to('suportegmbrasilvd@gmail.com')->send(new OrderPayment($orders, 'canceled', 'preservando'));
                User::whereIn('id', json_decode($orders->coupon->user_id))->get()->each(function ($query) {
                    Mail::to($query->email)->send(new OrderPayment($orders, 'canceled', 'afiliado'));
                });
            }
        }
    }

    public function pagarMe($request, $first_card)
    {
        $frete = number_format((session('transport')['price']), 2, '', '');
        $valor_preservando = $valor_total = number_format((session('transport')['price'] + (session()->get('coupon') ? session()->get('coupon')['value'] : cart_show()->total)), 2, '', '');
        if (isset($request->multiple_card)) {
            $valor_preservando = $valor_total = number_format(str_replace(['.', ','], ['', '.'], $request->parcial_value), 2, '', '');
        }

        $shipping_customer = ShippingCustomer::where('order_number', $this->order_number)->first();
        $splits = collect([]);

        if ($first_card) {
            if (session()->get('coupon')) {
                $coupon = Coupon::where('code', session()->get('coupon')['coupon'])->first();
                $afiliados = User::whereIn('id', json_decode($coupon->user_id))->get()->map(function ($query) use ($splits) {
                    $splits->add([
                        'amount' => number_format(session()->get('coupon')['desconto'], 2, '', ''),
                        'recipient_id' => $query->recipient_id,
                        'liable' => false,
                        'charge_processing_fee' => false
                    ]);
                });
                if (isset($request->multiple_card)) {
                    $valor_preservando = number_format((str_replace(['.', ','], ['', '.'], $request->parcial_value) - ($afiliados->count() * session()->get('coupon')['desconto'])), 2, '', '');
                } else {
                    $valor_preservando = number_format((session('transport')['price'] + (session()->get('coupon') ? session()->get('coupon')['value'] : cart_show()->total) - ($afiliados->count() * session()->get('coupon')['desconto'])), 2, '', '');
                }
            }
        }

        $splits->add([
            'amount' => $valor_preservando,
            'recipient_id' => ENV('PAGARME_API_RECEBEDOR_ID')
        ]);

        $items = collect(cart_show()->content)->map(function ($query) {
            return [
                'id' => $query->attributes->product_id,
                'title' => $query->name,
                'unit_price' => number_format($query->price, 2, '', ''),
                'quantity' => $query->quantity,
                'tangible' => true,
            ];
        });

        $phone2 = Str::of("{$shipping_customer->phone2}")->replaceMatches('/[^A-Za-z0-9]++/', '');
        $zipCode = Str::of($shipping_customer->post_code)->replaceMatches('/[^A-Za-z0-9]++/', '')->padLeft(8, 0);
        $payment = $this->paymentMethodParaEnviarPagarme($request);

        $createTransaction = collect([
            'api_key' => ENV('PAGARME_API_KEY'),
            'async' => true,
            'amount' => $valor_total,
            'postback_url' => 'http://137.184.124.183:82/postback/pagarme',
            'customer' => [
                'external_id' => (string)auth()->user()->id,
                'name' => auth()->user()->name,
                'type' => 'individual',
                'country' => 'br',
                'documents' => [
                    [
                        'type' => 'cpf',
                        'number' => auth()->user()->cnpj_cpf
                    ]
                ],
                'phone_numbers' => ["+55$phone2"],
                'email' => auth()->user()->email
            ],
            'billing' => [
                'name' => auth()->user()->name,
                'address' => [
                    'country' => 'br',
                    'street' => $shipping_customer->address,
                    'street_number' => $shipping_customer->number,
                    'state' => $shipping_customer->state,
                    'city' => $shipping_customer->city,
                    'neighborhood' => $shipping_customer->address2,
                    'zipcode' => $zipCode
                ]
            ],
            'shipping' => [
                'name' => auth()->user()->name,
                'fee' => $frete, // todo valor do frete
                'delivery_date' => now()->format('Y-m-d'),
                'expedited' => false,
                'address' => [
                    'country' => 'br',
                    'street' => $shipping_customer->address,
                    'street_number' => $shipping_customer->number,
                    'state' => $shipping_customer->state,
                    'city' => $shipping_customer->city,
                    'neighborhood' => $shipping_customer->address2,
                    'zipcode' => $zipCode
                ]
            ],
            'items' => $items->all(),
            'split_rules' => $splits->all()
        ]);
        $createTransaction = $createTransaction->merge($payment)->toArray();
        $post_pagarme = Http::post('https://api.pagar.me/1/transactions', $createTransaction)->object();

        \Log::info(collect($post_pagarme));
        PaymentOrder::create([
            'order_number' => $this->order_number,
            'payment_id' => $post_pagarme->id,
            'issuer_id' => $post_pagarme->acquirer_id,
            'payment_method_id' => $post_pagarme->payment_method,
            'payment_type_id' => $post_pagarme->card_brand,
            'status' => $post_pagarme->status,
            'status_detail' => $post_pagarme->status_reason,
            'currency_id' => 'BRL',
            'collector_id' => '',
            'net_received_amount' => 0,
            'total_paid_amount' => $post_pagarme->amount,
            'installments' => $post_pagarme->installments,
            'installment_amount' => $post_pagarme->amount / $post_pagarme->installments,
            'rate_mp' => 0,
            'payer_name' => $post_pagarme->card_holder_name,
            'payer_cnpj_cpf' => $post_pagarme->customer->documents[0]->number,
        ]);
        return $post_pagarme;
    }

    private function paymentMethodParaEnviarPagarme($request)
    {
        $carbon = new Carbon();
        $carbon->setMonth($request->card_expiration_month);
        $carbon->setYear($request->card_expiration_year);


        $payMethod = [
            'ticket' => [
                'payment_method' => 'boleto',
                'boleto_instructions' => ''
            ],
            'credit_card' => [
                'card_holder_name' => $request->card_holder_name,
                'card_expiration_date' => $carbon->format('mY'),
                'card_number' => $request->card_number,
                'card_cvv' => $request->card_cvv,
                'payment_method' => $request->payment_method,
                'installments' => $request->installments,
            ],
            'pix' => [
                'payment_method' => 'pix',
                'pix_expiration_date' => Carbon::now()->addDay()->format('Y-m-d')
            ]
        ];

        return $payMethod[$request->payment_method];
    }
}
