<?php

namespace App\Http\Controllers;

use ZipArchive;
use App\Models\User;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Address;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Promotion;
use App\Models\OrderProduct;
use App\Models\PaymentOrder;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\TransportValue;
use App\Models\ProductCategory;

use App\Models\ShippingCompany;
use App\Models\ShippingCustomer;
use Illuminate\Support\Facades\Storage;

class PainelController extends Controller
{
    public $sales_unit_array = [
        'P' => 'Peça',
        'M' => 'Metro',
        'MQ' => 'Metro Quadrado'
    ];

    public function dashboard()
    {
        return view('painel.dashboard');
    }

    public function indexCategoria($id = null)
    {
        if($id){
            $categories = Category::where('parent_id', $id)->get();
            $category_name = Category::where('id', $id)->first()->name;
        }else{
            $categories = Category::whereNull('parent_id')->with(['subCategories'])->get();
            $category_name = '';
        }
        return view('painel.cadastro.indexCategoria', compact('id', 'category_name', 'categories'));
    }

    public function indexProduto()
    {
        $sales_unit_array       = $this->sales_unit_array;

        $categories = Category::whereNull('parent_id')->get();

        $attributes = Attribute::with(['variations'])->whereNull('parent_id')->get();

        $products = Product::with(['productImage', 'productCategory', 'productAttribute'])->paginate(15);

        return view('painel.cadastro.indexProduto', compact('sales_unit_array', 'categories', 'products', 'attributes'));
    }

    public function indexPromocao()
    {
        $products = Product::with(['promotionP' => function($query){
            $query->where('category', 'N')->where('final_date', '>=', date('Y-m-d'))->where('active', 'S');
        }])->get();

        $main_categories = Category::whereNull('parent_id')->with(['promotionC' => function($query){
            $query->where('category', 'S')->where('final_date', '>=', date('Y-m-d'))->where('active', 'S');
        }])->get();

        $sub_categories = Category::whereNotNull('parent_id')->with(['promotionC' => function($query){
            $query->where('category', 'S')->where('final_date', '>=', date('Y-m-d'))->where('active', 'S');
        }])->get();

        $promotions = Promotion::where('active', 'S')->paginate(15);

        return view('painel.cadastro.indexPromocao', compact('products', 'main_categories', 'sub_categories', 'promotions'));
    }

    public function indexAtributo($id = null)
    {
        if($id){
            $attributes = Attribute::where('parent_id', $id)->get();
            $attribute_name = Attribute::where('id', $id)->first()->name;
        }else{
            $attributes = Attribute::whereNull('parent_id')->with(['variations'])->get();
            $attribute_name = '';
        }

        return view('painel.cadastro.indexAtributo', compact('id', 'attribute_name', 'attributes'));
    }

    public function indexTransporte($id = null)
    {
        if($id){
            $transport_values = TransportValue::where('shipping_company_id', $id)->get();
            $carrier_name = ShippingCompany::where('id', $id)->first()->carrier_name;

            return view('painel.indexTransValor', compact('id', 'transport_values', 'carrier_name'));
        }else{
            $shipping_companies = ShippingCompany::with(['transportValues'])->get();

            return view('painel.indexTransporte', compact('shipping_companies'));
        }
    }

    public function indexPerfil()
    {
        return view('painel.indexPerfil');
    }

    public function indexContas()
    {
        $accounts = User::where('id', '!=', auth()->user()->id)->where('permission', 10)->paginate(10);

        return view('painel.indexContas', compact('accounts'));
    }

    public function indexClientes()
    {
        $accounts = User::with(['adresses'])->whereIn('permission', [0,2])->paginate(10);

        return view('painel.cliente.indexClientes', compact('accounts'));
    }

    public function indexAfiliados()
    {
        $accounts = User::with(['adresses', 'bank'])->where('permission', 2)->paginate(10);

        return view('painel.cliente.indexAfiliados', compact('accounts'));
    }

    public function indexEnderecos($id)
    {
        $user = User::where('id', $id)->first();
        $addresses = Address::where('user_id', $id)->get();

        return view('painel.cliente.indexEnderecos', compact('user', 'addresses'));
    }

    public function indexCupons()
    {
        $afiliados = User::where('permission', 2)->get();
        $coupons = Coupon::where('active', 'S')->paginate(8);

        return view('painel.cupom.indexCupons', compact('afiliados', 'coupons'));
    }

    public function indexPedidos()
    {
        $orders = Order::where('active', 'S')->orderBy('order_number', 'DESC')->with(['orderProducts', 'shippingCustomer', 'paymentOrder'])->paginate(10);

        return view('painel.comercial.indexPedidos', compact('orders'));
    }

    public function gerarRelatorioPedidos(Request $request)
    {
        $data_ini = date('Y-m-d', strtotime(str_replace('/','-',explode(' - ', $request->start_end_date)[0])));
        $data_fim = date('Y-m-d', strtotime(str_replace('/','-',explode(' - ', $request->start_end_date)[1])));
        $orders = Order::where('active', 'S')->whereDate('created_at', '>=', $data_ini)->whereDate('created_at', '<=', $data_fim);
        if($request->status_pedido !== 'todos'){
            $orders = $orders->where('pay', $request->status_pedido);
        }

        $order_csv = collect([['Numero do Pedido', 'Nome do Cliente', 'Email do Cliente', 'Valor Total', 'Custo do Frete', 'Valor dos Produtos', 'Status', 'Data']]);
        $orders = $orders->get()->map(function ($query)use($order_csv){
            $order_csv->add([
                $query->order_number,
                $query->user_name,
                $query->user_email,
                $query->total_value,
                $query->cost_freight,
                $query->product_value,
                $query->pay,
                date('d/m/Y', strtotime($query->created_at)),
            ]);
            return [
                'total' => $query->total_value,
                'freight' => $query->cost_freight,
                'product' => $query->product_value,
            ];
        });
        $order_csv->add([
            'Total',
            '.',
            '.',
            $orders->sum('total'),
            $orders->sum('freight'),
            $orders->sum('product'),
            '.',
            '.',
        ]);
        // dd($order_csv);

        $fileName = date('Y-m-d').'.csv';
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use($order_csv) {
            $file = fopen('php://output', 'w');
            foreach ($order_csv as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}