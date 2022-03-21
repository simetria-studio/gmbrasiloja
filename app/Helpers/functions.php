<?php

if(!function_exists('getCategories')){
    function getCategories(){
        $categories = App\Models\Category::whereNull('parent_id')->with(['subCategories'])->get();

        return $categories;
    }
}
if(!function_exists('getCategoryServices')){
    function getCategoryServices(){
        $services = [
            [
                'href' => route('indexSobreNos'),
                'name' => 'SOBRE NÓS',
            ],
        
            [
                'href' => route('indexFaq'),
                'name' => 'FAQ',
            ],
            [
                'href' => route('indexTutorial'),
                'name' => 'TUTORIAS',
            ],
            [
                'href' => route('indexFaleConosco'),
                'name' => 'CONTATO',
            ],
        ];

        return json_decode(json_encode($services));
    }
}

if(!function_exists('attribute_select')){
    function attribute_select($attributes){
        $array_attribute = [];
        $parent_id = [];
        foreach($attributes as $attribute){
            $parent_id[] = $attribute->parent_id;
            $attribute_sub = App\Models\Attribute::where('id', $attribute->attribute_id)->first();
            $array_attribute[$attribute->parent_id][] = [
                'attribute_id' => $attribute->attribute_id,
                'attribute_value' => $attribute->attribute_value,
                'attribute_name' => $attribute->attribute_name,
                'hexadecimal' => $attribute_sub->hexadecimal ?? '',
                'image' => $attribute_sub->image ?? ''
            ];
        }

        $parent_id = array_unique($parent_id);
        $attribute_names = [];
        foreach($parent_id as $par_id){
            $attribute_sub = App\Models\Attribute::where('id', $par_id)->first();
            $attribute_names[] = [
                'parent_id' => $par_id,
                'name' => $attribute_sub->name
            ];
        }

        return json_decode(json_encode([
            'attribute_p' => $attribute_names,
            'attribute_s' => $array_attribute
        ]));

    }
}

if(!function_exists('cart_show')){
    function cart_show(){
        $cart_contents = Darryldecode\Cart\Facades\CartFacade::getContent();

        $carts = [];
        foreach($cart_contents as $contents){
            $cart['row_id']     = $contents->id;
            $cart['name']       = $contents->name;
            $cart['price']      = $contents->price;
            $cart['quantity']   = $contents->quantity;
            $cart['attributes'] = [
                'product_id'            => $contents->attributes->product_id,
                'has_preparation'       => $contents->attributes->has_preparation,
                'preparation_time'      => $contents->attributes->preparation_time,
                'product_value'         => $contents->attributes->product_value,
                'product_p_value'       => $contents->attributes->product_p_value,
                'product_p_porcent'     => $contents->attributes->product_p_porcent,
                'product_promotion'     => $contents->attributes->product_promotion,
                'product_image'         => $contents->attributes->product_image,
                'product_weight'        => $contents->attributes->product_weight,
                'product_height'        => $contents->attributes->product_height,
                'product_width'         => $contents->attributes->product_width,
                'product_length'        => $contents->attributes->product_length,
                'product_sales_unit'    => $contents->attributes->product_sales_unit,
                'project_value'         => $contents->attributes->project_value,
                'project_width'         => $contents->attributes->project_width,
                'project_height'        => $contents->attributes->project_height,
                'project_meters'        => $contents->attributes->project_meters,
                'attributes_aux'        => $contents->attributes->attributes_aux,
                'project'               => $contents->attributes->project,
                'note'                  => $contents->attributes->note,
            ];

            if(auth()->check()){
                $cart['user_id']    = auth()->user()->id;
                $cart['active']     = 'S';
                Darryldecode\Cart\Facades\CartFacade::remove($contents->id);

                $cart_row_id = App\Models\Cart::where('user_id', auth()->user()->id)->first([DB::raw('MAX(row_id) as row_id')]);
                $cart['row_id'] = $cart_row_id->row_id+1;
                App\Models\Cart::create($cart);
            }

            $carts[] = $cart;
        }

        $carts = json_decode(json_encode($carts));

        if(auth()->check()){
            $carts = App\Models\Cart::where('user_id', auth()->user()->id)->get();
        }

        $carts = json_decode(json_encode($carts));

        $total_cart = 0;
        $quantity_cart = 0;
        $originalValue = 0;
        foreach($carts as $total){
            $total_cart += ($total->price * $total->quantity);
            $quantity_cart += $total->quantity;

            if($total->attributes->product_promotion == 'S'){
                $product_value = (float)$total->attributes->product_value;
                $project_meters = (float)$total->attributes->project_meters;
                $project_value = (float)$total->attributes->project_value;
                $product_p_value = (float)$total->attributes->product_p_value;
                $product_p_porcent = (float)$total->attributes->product_p_porcent;

                $originalValue += ($project_meters !== 0 ? (($product_value * $project_meters)+($total->price - $project_value)) : ($product_value+($total->price - $product_p_value)) * $total->quantity);
            }else{
                $originalValue += ($total->price * $total->quantity);
            }
        }

        return json_decode(json_encode(['content' => $carts, 'total' => $total_cart, 'quantidade' => $quantity_cart, 'original_value' => $originalValue]));
    }
}

if(!function_exists('getPricePromotion')){
    function getPricePromotion($product_id, $product_value, $categories){
        ##############################
        /////////////REGRA////////////
        ##O valor da promoção do produto prevale como superior##
        ##O valor da categoria fica como secundario caso o do produto não tenha promoção##
        ##Quando não tiver promoção na catgoria pai ou produto as subcategoria toma o lugar, quando o produto esta em mais d duas categorias o produto pega o o desconto maior##
        ////////////FIM DA REGRA//////
        ##############################
        $promotions = App\Models\Promotion::where('start_date', '<=', date('Y-m-d'))->where('final_date', '>=', date('Y-m-d'))->where('active', 'S')->get();

        $subPromotions = [];
        foreach($promotions as $promotion){
            if($promotion->category == 'N'){ // quando não tem catgoria ou mesmo se tiver categoria o do rpoduto prevalece
                if($promotion->identifier == $product_id){ // Identificando os ids
                    return ['value' => ($product_value - (($product_value * $promotion->value) / 100)), 'porcent' => $promotion->value];
                }
            }else if($promotion->category == 'S'){
                foreach($categories as $category){
                    if($category->category_pai == 'S'){
                        if($promotion->identifier == $category->category_id){
                            return ['value' => ($product_value - (($product_value * $promotion->value) / 100)), 'porcent' => $promotion->value];
                        }
                    }else if($category->category_pai == 'N'){
                        if($promotion->identifier == $category->category_id){
                            $subPromotions[] = $promotion->value;
                        }
                    }
                }
            }
        }

        if(count($subPromotions) > 0){
            $value = max($subPromotions);
            return ['value' => ($product_value - (($product_value * $value) / 100)), 'porcent' => $value];
        }

        return false;
    }
}

if (!function_exists('bancos')) {
    /**
     * @return string[]
     */
    function bancos(): array
    {
        return $bancos = array(
            '001' => 'Banco do Brasil S.A.',
            '003' => 'Banco da Amazônia S.A.',
            '004' => 'Banco do Nordeste do Brasil S.A.',
            '007' => 'Banco Nacional de Desenvolvimento Econômico e Social - BNDES',
            '012' => 'Banco Inbursa S.A.',
            '014' => 'State Street Brasil S.A. - Banco Comercial',
            '017' => 'BNY Mellon Banco S.A.',
            '018' => 'Banco Tricury S.A.',
            '021' => 'BANESTES S.A. Banco do Estado do Espírito Santo',
            '024' => 'Banco BANDEPE S.A.',
            '025' => 'Banco Alfa S.A.',
            '027' => 'Besc',
            '029' => 'Banco Itaú Consignado S.A.',
            '031' => 'Banco Beg',
            '033' => 'Banco Santander  (Brasil)  S.A.',
            '036' => 'Banco Bradesco BBI S.A.',
            '037' => 'Banco do Estado do Pará S.A.',
            '038' => 'Banestado',
            '039' => 'BEP',
            '040' => 'Banco Cargill S.A.',
            '041' => 'Banco do Estado do Rio Grande do Sul S.A.',
            '044' => 'BVA',
            '045' => 'Banco Opportunity',
            '047' => 'Banco do Estado de Sergipe S.A.',
            '051' => 'Banco de Desenvolvimento do Espírito Santo S.A.',
            '062' => 'Hipercard Banco Múltiplo S.A.',
            '063' => 'Banco Bradescard S.A.',
            '064' => 'Goldman Sachs do Brasil Banco Múltiplo S.A.',
            '065' => 'Banco Andbank (Brasil) S.A.',
            '066' => 'Banco Morgan Stanley S.A.',
            '069' => 'Banco Crefisa S.A.',
            '070' => 'BRB - Banco de Brasília S.A.',
            '072' => 'Banco Rural',
            '073' => 'Banco Popular',
            '074' => 'Banco J. Safra S.A.',
            '075' => 'Banco ABN AMRO S.A.',
            '076' => 'Banco KDB S.A.',
            '077' => 'Banco Inter S.A.',
            '078' => 'Haitong Banco de Investimento do Brasil S.A.',
            '079' => 'Banco Original do Agronegócio S.A.',
            '081' => 'BancoSeguro S.A.',
            '082' => 'Banco Topázio S.A.',
            '083' => 'Banco da China Brasil S.A.',
            '084' => 'Uniprime Norte do Paraná - Coop de Economia e Crédito Mútuo dos Médicos, Profissionais das Ciências',
            '085' => 'Cooperativa Central de Crédito - AILOS',
            '092' => 'Brickell S.A. Crédito, Financiamento e Investimento',
            '094' => 'Banco Finaxis S.A.',
            '095' => 'Travelex Banco de Câmbio S.A.',
            '096' => 'Banco B3 S.A.',
            '097' => 'Cooperativa Central de Crédito Noroeste Brasileiro Ltda.',
            '102' => 'Banco XP S.A.',
            '104' => 'Caixa Econômica Federal',
            '107' => 'Banco BOCOM BBM S.A.',
            '118' => 'Standard Chartered Bank (Brasil) S/A–Bco Invest.',
            '116' => 'Banco Único',
            '119' => 'Banco Western Union do Brasil S.A.',
            '120' => 'Banco Rodobens S.A.',
            '121' => 'Banco Agibank S.A.',
            '122' => 'Banco Bradesco BERJ S.A.',
            '124' => 'Banco Woori Bank do Brasil S.A.',
            '125' => 'Banco Genial S.A.',
            '126' => 'BR Partners Banco de Investimento S.A.',
            '128' => 'MS Bank S.A. Banco de Câmbio',
            '129' => 'UBS Brasil Banco de Investimento S.A.',
            '132' => 'ICBC do Brasil Banco Múltiplo S.A.',
            '136' => 'Banco Unicred',
            '139' => 'Intesa Sanpaolo Brasil S.A. - Banco Múltiplo',
            '144' => 'BEXS Banco de Câmbio S.A.',
            '151' => 'Nossa Caixa',
            '163' => 'Commerzbank Brasil S.A. - Banco Múltiplo',
            '169' => 'Banco Olé Bonsucesso Consignado S.A.',
            '175' => 'Banco Finasa',
            '184' => 'Banco Itaú BBA S.A.',
            '204' => 'Banco Bradesco Cartões S.A.',
            '208' => 'Banco BTG Pactual S.A.',
            '212' => 'Banco Original S.A.',
            '213' => 'Banco Arbi S.A.',
            '214' => 'Banco Dibens',
            '217' => 'Banco John Deere S.A.',
            '218' => 'Banco BS2 S.A.',
            '222' => 'Banco Credit Agricole Brasil S.A.',
            '224' => 'Banco Fibra S.A.',
            '225' => 'Banco Brascan',
            '229' => 'Banco Cruzeiro',
            '230' => 'Unicard',
            '233' => 'Banco Cifra S.A.',
            '237' => 'Banco Bradesco S.A.',
            '241' => 'Banco Clássico S.A.',
            '243' => 'Banco Master S.A.',
            '246' => 'Banco ABC Brasil S.A.',
            '248' => 'Banco Boavista Interatlântico',
            '249' => 'Banco Investcred Unibanco S.A.',
            '250' => 'BCV - Banco de Crédito e Varejo S.A.',
            '252' => 'Fininvest',
            '254' => 'Paraná Banco S.A.',
            '263' => 'Banco Cacique',
            '265' => 'Banco Fator S.A.',
            '266' => 'Banco Cédula S.A.',
            '269' => 'HSBC Brasil S.A. - Banco de Investimento',
            '276' => 'Banco Senff S.A.',
            '299' => 'Banco Sorocred S.A. - Banco Múltiplo (AFINZ)',
            '300' => 'Banco de La Nacion Argentina',
            '318' => 'Banco BMG S.A.',
            '320' => 'China Construction Bank (Brasil) Banco Múltiplo S.A.',
            '330' => 'Banco Bari de Investimentos e Financiamentos S/A',
            '341' => 'Itaú Unibanco S.A.',
            '347' => 'Sudameris',
            '351' => 'Banco Santander',
            '353' => 'Banco Santander Brasil',
            '356' => 'ABN Amro Real',
            '359' => 'Zema Credito, Financiamento e Investimento S.A.',
            '366' => 'Banco Société Générale Brasil S.A.',
            '370' => 'Banco Mizuho do Brasil S.A.',
            '376' => 'Banco J. P. Morgan S.A.',
            '389' => 'Banco Mercantil do Brasil S.A.',
            '394' => 'Banco Bradesco Financiamentos S.A.',
            '399' => 'Kirton Bank S.A. - Banco Múltiplo',
            '409' => 'Unibanco',
            '412' => 'Banco Capital S.A.',
            '422' => 'Banco Safra S.A.',
            '453' => 'Banco Rural',
            '456' => 'Banco MUFG Brasil S.A.',
            '464' => 'Banco Sumitomo Mitsui Brasileiro S.A.',
            '473' => 'Banco Caixa Geral - Brasil S.A.',
            '477' => 'Citibank N.A.',
            '479' => 'Banco ItauBank S.A',
            '487' => 'Deutsche Bank S.A. - Banco Alemão',
            '488' => 'JPMorgan Chase Bank, National Association',
            '492' => 'ING Bank N.V.',
            '494' => 'Banco de La Republica Oriental del Uruguay',
            '495' => 'Banco de La Provincia de Buenos Aires',
            '505' => 'Banco Credit Suisse (Brasil) S.A.',
            '600' => 'Banco Luso Brasileiro S.A.',
            '604' => 'Banco Industrial do Brasil S.A.',
            '610' => 'Banco VR S.A.',
            '611' => 'Banco Paulista S.A.',
            '612' => 'Banco Guanabara S.A.',
            '613' => 'Omni Banco S.A.',
            '623' => 'Banco PAN S.A.',
            '626' => 'Banco C6 Consignado S.A.',
            '630' => 'Banco Letsbank S.A.',
            '633' => 'Banco Rendimento S.A.',
            '634' => 'Banco Triângulo S.A.',
            '637' => 'Banco Sofisa S.A.',
            '638' => 'Banco Prosper',
            '641' => 'Banco Alvorada S.A.',
            '643' => 'Banco Pine S.A.',
            '652' => 'Itaú Unibanco Holding S.A.',
            '653' => 'Banco Voiter S.A.',
            '654' => 'Banco Digimais S.A.',
            '655' => 'Banco Votorantim S.A.',
            '658' => 'Banco Porto Real de Investimentos S.A.',
            '707' => 'Banco Daycoval S.A.',
            '712' => 'Banco Ourinvest S.A.',
            '719' => 'Banif',
            '720' => 'BANCO RNX S.A',
            '721' => 'Banco Credibel',
            '734' => 'Banco Gerdau',
            '735' => 'Banco Pottencial',
            '738' => 'Banco Morada',
            '739' => 'Banco Cetelem S.A.',
            '740' => 'Banco Barclays',
            '741' => 'Banco Ribeirão Preto S.A.',
            '743' => 'Banco Semear S.A.',
            '745' => 'Banco Citibank S.A.',
            '746' => 'Banco Modal S.A.',
            '747' => 'Banco Rabobank International Brasil S.A.',
            '748' => 'Banco Cooperativo Sicredi S.A.',
            '749' => 'Banco Simples',
            '751' => 'Scotiabank Brasil S.A. Banco Múltiplo',
            '752' => 'Banco BNP Paribas Brasil S.A.',
            '753' => 'Novo Banco Continental S.A. - Banco Múltiplo',
            '754' => 'Banco Sistema S.A.',
            '755' => 'Bank of America Merrill Lynch Banco Múltiplo S.A.',
            '756' => 'Banco Cooperativo do Brasil S.A. - BANCOOB',
            '757' => 'Banco KEB HANA do Brasil S.A.',
            '087-6' => 'Cooperativa Central de Economia e Crédito Mútuo das Unicreds de Santa Catarina e Paraná',
            '089-2' => 'Cooperativa de Crédito Rural da Região da Mogiana',
            '090-2' => 'Cooperativa Central de Economia e Crédito Mutuo - SICOOB UNIMAIS',
            '091-4' => 'Unicred Central do Rio Grande do Sul',
            '098-1' => 'CREDIALIANÇA COOPERATIVA DE CRÉDITO RURAL',
            '114-7' => 'Central das Cooperativas de Economia e Crédito Mútuo do Estado do Espírito Santo Ltda.',
            '-' => 'cc do norte catarinense e sul paranaense',
            '' => 'cc do norte catarinense e sul paranaense',
        );
    }
}

if (!function_exists('printInformacaoBanco')) {
    /**
     * @param $campo
     * @return string
     */
    function printInformacaoBanco($object, $campo, $label, callable $callbackGetValor = null): string
    {
        $valor = $object->{$campo};
        if (!is_null($callbackGetValor)) {
            $valor = $callbackGetValor($valor, $object);
        }

        return "$label: <strong>{$valor}</strong>";
    }
}

if (!function_exists('print_valor_vindo_pagar_me')) {
    /**
     * @param $valor
     * @return string
     */
    function print_valor_vindo_pagar_me($valor): string
    {
        $valor = number_format(($valor / 100),2, ',', '.');

        return "R$ {$valor}";
    }
}
