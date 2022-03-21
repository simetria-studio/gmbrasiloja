<?php

namespace App\Http\Controllers;

use App\Models\user;
use App\Models\Address;
use Illuminate\Http\Request;
use App\Models\AffiliateBank;
use App\Models\RequestAfiliado;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    /** ############## PERFIL ################### */
    public function nomePerfil(Request $request)
    {
        $perfilName['name'] = $request->name;

        User::where('id', auth()->user()->id)->update($perfilName);

        return response()->json('Sucesso!');
    }

    public function emailPerfil(Request $request)
    {
        $request->validate([
            'email' => 'unique:users,email,'.auth()->user()->id,
        ]);

        $perfilEmail['email'] = $request->email;

        User::where('id', auth()->user()->id)->update($perfilEmail);

        return response()->json('Sucesso!');
    }

    public function senhaPerfil(Request $request)
    {
        if(Hash::check($request->current_password, auth()->user()->password)){
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
            
            User::where('id',auth()->user()->id)->update([
                'password' => Hash::make($request['password']),
            ]);

            return response()->json(['success' => 'Senha Atualizada com Sucesso!!']);
        }else{
            return response()->json(['errors' => ['current_password' => ['Senha Atual invalida!']]], 422);
        }
    }

    /** ############## CONTAS DO PAINEL ################### */
    public function novaConta(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $account = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'permission' => 10,
        ]);

        return response()->json([
            'table' => '<tr class="'.$account->id.'">
                <td>'.$account->id.'</td>
                <td>'.$account->name.'</td>
                <td>'.$account->email.'</td>
                <td>
                    <div class="btn-group" role="group" aria-label="">
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#atualizarSenha" data-dados="'.json_encode($account).'"><i class="fas fa-edit"></i> Trocar Senha</a>
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#editarConta" data-dados="'.json_encode($account).'"><i class="fas fa-edit"></i> Editar Nome & Email</a>
                        <a href="#" class="btn btn-danger btn-sm btn-editar" data-toggle="modal" data-target="#excluirConta" data-dados="'.json_encode($account).'"><i class="fas fa-trash"></i> Apagar Conta</a>
                    </div>
                </td>
            </tr>'
        ]);
    }

    public function atualizarConta(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'unique:users,email,'.$request->id,
        ]);

        $accounts['name'] = $request->name;
        $accounts['email'] = $request->email;

        User::where('id', $request->id)->update($accounts);
        $account = User::where('id', $request->id)->first();

        return response()->json([
            'tb_id' => $account->id,
            'tb_up' => '
                <td>'.$account->id.'</td>
                <td>'.$account->name.'</td>
                <td>'.$account->email.'</td>
                <td>
                    <div class="btn-group" role="group" aria-label="">
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#atualizarSenha" data-dados="'.json_encode($account).'"><i class="fas fa-edit"></i> Trocar Senha</a>
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#editarConta" data-dados="'.json_encode($account).'"><i class="fas fa-edit"></i> Editar Nome & Email</a>
                        <a href="#" class="btn btn-danger btn-sm btn-editar" data-toggle="modal" data-target="#excluirConta" data-dados="'.json_encode($account).'"><i class="fas fa-trash"></i> Apagar Conta</a>
                    </div>
                </td>'
        ]);
    }

    public function excluirConta(Request $request)
    {
        User::where('id', $request->id)->delete();

        return response()->json([
            'tb_trash' => $request->id
        ]);
    }

    public function atualizarSenha(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        
        User::where('id',$request->id)->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['success' => 'Senha Atualizada com Sucesso!!']);
    }

    /** ############## CONTAS DE USUARIOS ################### */
    public function novoCliente(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $account = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'cnpj_cpf' => $request->cnpj_cpf,
            'password' => Hash::make($request->password),
            'permission' => 0,
        ]);

        if($request->afiliado_check){
            $collectSendRecipients = [
                'api_key' => ENV('PAGARME_API_KEY'),
                // message: "ERROR TYPE: invalid_parameter. PARAMETER: external_id. MESSAGE: External ID must be unique"
                'external_id' => md5($account->id.$request->get('cnpj_cpf')),

                // a regra de transferência é no primeiro dia útil do mês
                'transfer_interval' => 'monthly',
                'transfer_day' => 1,
                'transfer_enabled' => true,

                'bank_account' => [
                    'bank_code' => $request->get('bank_code'),
                    'agencia' => $request->get('agencia'),
                    'agencia_dv' => empty($request->get('agencia_dv')) ? 0 : $request->get('agencia_dv'),
                    'conta' => $request->get('conta'),
                    'type' => $request->get('type'),
                    'conta_dv' => $request->get('conta_dv'),
                    'document_number' => $request->get('cnpj_cpf'),
                    'legal_name' => $request->get('legal_name'),
                ]
            ];

            $post_pagarme_recipient = Http::post('https://api.pagar.me/1/recipients', $collectSendRecipients)->object();

            User::find($account->id)->update(['permission' => 2, 'recipient_id' => $post_pagarme_recipient->id]);
            $account = User::find($account->id);
        }

        return response()->json([
            'table' => '<tr class="tr-id-'.$account->id.'">
                <td>'.$account->id.'</td>
                <td>'.$account->name.'</td>
                <td>'.$account->cnpj_cpf.'</td>
                <td>'.$account->email.'</td>
                <td>'.($account->recipient_id ? '<span class="bg-success rounded px-2 py-1">'.$account->recipient_id.'</span>' : '<span class="bg-info rounded px-2 py-1">Não Afiliado</span>').'</td>
                <td><a href="'.url('admin/cliente/enderecos', $account->id).'" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Visualizar</a></td>
                <td>
                    <div class="btn-group" role="group" aria-label="">
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#atualizarSenha" data-dados=\''.json_encode($account).'\'><i class="fas fa-edit"></i> Trocar Senha</a>
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#editarCliente" data-dados=\''.json_encode($account).'\'><i class="fas fa-edit"></i> Editar Cliente</a>
                        <a href="#" class="btn btn-danger btn-sm btn-editar" data-toggle="modal" data-target="#excluirConta" data-dados=\''.json_encode($account).'\'><i class="fas fa-trash"></i> Apagar Conta</a>
                    </div>
                </td>
            </tr>'
        ]);
    }

    public function novoAfiliado(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $account = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'cnpj_cpf' => $request->cnpj_cpf,
            'password' => Hash::make($request->password),
            'permission' => 2,
        ]);

        // AffiliateBank::create(['user_id'=>$account->id]);

        return response()->json([
            'table' => '<tr class="tr-id-'.$account->id.'">
                <td>'.$account->id.'</td>
                <td>'.$account->name.'</td>
                <td>'.$account->cnpj_cpf.'</td>
                <td>'.$account->email.'</td>
                <td><button type="button" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#pagarAfiliado" data-dados=\''.json_encode($account).'\'><i class="fas fa-dollar-sign"></i></button></td>
                <td><a href="'.url('admin/cliente/enderecos', $account->id).'" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Visualizar</a></td>
                <td>
                    <div class="btn-group" role="group" aria-label="">
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#atualizarSenha" data-dados=\''.json_encode($account).'\'><i class="fas fa-edit"></i> Trocar Senha</a>
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#editarCliente" data-dados=\''.json_encode($account).'\'><i class="fas fa-edit"></i> Editar Cliente</a>
                        <a href="#" class="btn btn-danger btn-sm btn-editar" data-toggle="modal" data-target="#excluirConta" data-dados=\''.json_encode($account).'\'><i class="fas fa-trash"></i> Apagar Conta</a>
                    </div>
                </td>
            </tr>'
        ]);
    }

    public function atualizarCliente(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'unique:users,email,'.$request->id,
        ]);

        $accounts['name'] = $request->name;
        $accounts['email'] = $request->email;
        $accounts['cnpj_cpf'] = $request->cnpj_cpf;

        if($request->afiliado_check){
            $collectSendRecipients = [
                'api_key' => ENV('PAGARME_API_KEY'),
                // message: "ERROR TYPE: invalid_parameter. PARAMETER: external_id. MESSAGE: External ID must be unique"
                'external_id' => md5($request->id.$request->get('cnpj_cpf')),

                // a regra de transferência é no primeiro dia útil do mês
                'transfer_interval' => 'monthly',
                'transfer_day' => 1,
                'transfer_enabled' => true,

                'bank_account' => [
                    'bank_code' => $request->get('bank_code'),
                    'agencia' => $request->get('agencia'),
                    'agencia_dv' => empty($request->get('agencia_dv')) ? 0 : $request->get('agencia_dv'),
                    'conta' => $request->get('conta'),
                    'type' => $request->get('type'),
                    'conta_dv' => $request->get('conta_dv'),
                    'document_number' => $request->get('cnpj_cpf'),
                    'legal_name' => $request->get('legal_name'),
                ]
            ];

            $post_pagarme_recipient = Http::post('https://api.pagar.me/1/recipients', $collectSendRecipients)->object();

            $accounts['permission'] = 2;
            $accounts['narecipient_idme'] = $post_pagarme_recipient->id;
        }
        if($request->afiliado_check_update){
            $collectSendRecipients = [
                'api_key' => ENV('PAGARME_API_KEY'),
                // message: "ERROR TYPE: invalid_parameter. PARAMETER: external_id. MESSAGE: External ID must be unique"
                'external_id' => md5($request->id.$request->get('cnpj_cpf')),

                // a regra de transferência é no primeiro dia útil do mês
                'transfer_interval' => 'monthly',
                'transfer_day' => 1,
                'transfer_enabled' => true,

                'bank_account' => [
                    'bank_code' => $request->get('bank_code'),
                    'agencia' => $request->get('agencia'),
                    'agencia_dv' => empty($request->get('agencia_dv')) ? 0 : $request->get('agencia_dv'),
                    'conta' => $request->get('conta'),
                    'type' => $request->get('type'),
                    'conta_dv' => $request->get('conta_dv'),
                    'document_number' => $request->get('cnpj_cpf'),
                    'legal_name' => $request->get('legal_name'),
                ]
            ];

            $post_pagarme_recipient = Http::post('https://api.pagar.me/1/recipients', $collectSendRecipients)->object();

            $accounts['permission'] = 2;
            $accounts['narecipient_idme'] = $post_pagarme_recipient->id;
        }

        User::where('id', $request->id)->update($accounts);
        $account = User::where('id', $request->id)->first();

        return response()->json([
            'tb_id' => $account->id,
            'tb_up' => '
                <td>'.$account->id.'</td>
                <td>'.$account->name.'</td>
                <td>'.$account->cnpj_cpf.'</td>
                <td>'.$account->email.'</td>
                <td>'.($account->recipient_id ? '<span class="bg-success rounded px-2 py-1">'.$account->recipient_id.'</span>' : '<span class="bg-info rounded px-2 py-1">Não Afiliado</span>').'</td>
                <td><a href="'.url('admin/cliente/enderecos', $account->id).'" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Visualizar</a></td>
                <td>
                    <div class="btn-group" role="group" aria-label="">
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#atualizarSenha" data-dados=\''.json_encode($account).'\'><i class="fas fa-edit"></i> Trocar Senha</a>
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#editarCliente" data-dados=\''.json_encode($account).'\'><i class="fas fa-edit"></i> Editar Cliente</a>
                        <a href="#" class="btn btn-danger btn-sm btn-editar" data-toggle="modal" data-target="#excluirConta" data-dados=\''.json_encode($account).'\'><i class="fas fa-trash"></i> Apagar Conta</a>
                    </div>
                </td>'
        ]);
    }

    public function excluirCliente(Request $request)
    {
        User::where('id', $request->id)->delete();
        Address::where('user_id', $request->id)->delete();

        return response()->json([
            'tb_trash' => $request->id
        ]);
    }

    public function atualizarSenhaCliente(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        
        User::where('id',$request->id)->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['success' => 'Senha Atualizada com Sucesso!!']);
    }

    /** ############## ENDEREÇOS ################### */
    public function novoEndereco(Request $request)
    {
        $request->validate([
            'post_code'       => 'required',
            'address'   => 'required',
            'number'    => 'required',
            'address2'  => 'required',
            'state'     => 'required',
            'city'      => 'required',
            'phone2'    => 'required',
        ]);

        $addresses['user_id']       = $request->user_id;
        $addresses['post_code']     = $request->post_code;
        $addresses['address']       = $request->address;
        $addresses['number']        = $request->number;
        $addresses['complement']    = $request->complement;
        $addresses['address2']      = $request->address2;
        $addresses['state']         = $request->state;
        $addresses['city']          = $request->city;
        $addresses['phone1']        = $request->phone1;
        $addresses['phone2']        = $request->phone2;

        if(Address::where('user_id', $request->user_id)->get()->count() == 4) return response()->json(['msg_alert' => 'Maximo 4 endereço nesse usuario!', 'icon_alert' => 'warning'], 412);

        $address = Address::create($addresses);

        return response()->json([
            'table' => '<tr class="tr-id-'.$address->id.'">
                <td>'.$address->id.'</td>
                <td>'.$address->post_code.'</td>
                <td>'.$address->address.' - '.$address->number.'</td>
                <td>'.$address->complement.'</td>
                <td>'.$address->address2.'</td>
                <td>'.$address->city.'</td>
                <td>'.$address->state.'</td>
                <td>'.$address->phone1.' // '.$address->phone2.'</td>
                <td>
                    <div class="btn-group" role="group" aria-label="">
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#editarEndereco" data-dados=\''.json_encode($address).'\'><i class="fas fa-edit"></i> Editar</a>
                        <a href="#" class="btn btn-danger btn-sm btn-editar" data-toggle="modal" data-target="#excluirEndereco" data-dados=\''.json_encode($address).'\'><i class="fas fa-trash"></i> Apagar</a>
                    </div>
                </td>
            </tr>'
        ]);
    }

    public function atualizarEndereco(Request $request)
    {
        $request->validate([
            'post_code' => 'required',
            'address'   => 'required',
            'number'    => 'required',
            'address2'  => 'required',
            'state'     => 'required',
            'city'      => 'required',
            'phone2'    => 'required',
        ]);

        $addresses['post_code']     = $request->post_code;
        $addresses['address']       = $request->address;
        $addresses['number']        = $request->number;
        $addresses['complement']    = $request->complement;
        $addresses['address2']      = $request->address2;
        $addresses['state']         = $request->state;
        $addresses['city']          = $request->city;
        $addresses['phone1']        = $request->phone1;
        $addresses['phone2']        = $request->phone2;

        Address::where('id', $request->id)->update($addresses);
        $address = Address::where('id', $request->id)->first();

        return response()->json([
            'tb_id' => $address->id,
            'tb_up' => '
                <td>'.$address->id.'</td>
                <td>'.$address->post_code.'</td>
                <td>'.$address->address.' - '.$address->number.'</td>
                <td>'.$address->complement.'</td>
                <td>'.$address->address2.'</td>
                <td>'.$address->city.'</td>
                <td>'.$address->state.'</td>
                <td>'.$address->phone1.' // '.$address->phone2.'</td>
                <td>
                    <div class="btn-group" role="group" aria-label="">
                        <a href="#" class="btn btn-info btn-sm btn-editar" data-toggle="modal" data-target="#editarEndereco" data-dados=\''.json_encode($address).'\'><i class="fas fa-edit"></i> Editar</a>
                        <a href="#" class="btn btn-danger btn-sm btn-editar" data-toggle="modal" data-target="#excluirEndereco" data-dados=\''.json_encode($address).'\'><i class="fas fa-trash"></i> Apagar</a>
                    </div>
                </td>'
        ]);
    }

    public function excluirEndereco(Request $request)
    {
        Address::where('id', $request->id)->delete();

        return response()->json([
            'tb_trash' => $request->id
        ]);
    }


    ################## Dados do cliente vindo do site ##################
    public function perfilSave(Request $request)
    {
        User::find(auth()->user()->id)->update([
            'name'  => $request->name,
            'cpf'   => $request->cpf,
            'birth_date' => $request->birth_date ? date('Y-m-d', strtotime(str_replace('/','-', $request->birth_date))) : null,
        ]);

        return redirect('/perfil')->with('success', 'Seus dados foram atualizados com successo!');
    }

    public function senhaSave(Request $request)
    {
        Validator::extend('current_password', function ($attribute, $value, $parameters, $validator) {
            $auth = User::find(auth()->user()->id);
        
            return $auth && Hash::check($value, $auth->password);
        });

        $request->validate([
            'current_password' => ['required', 'string', 'min:8', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $auth = User::find(auth()->user()->id);

        $auth->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect('perfil')->with('success', 'Senha alterada com sucesso!');
    }

    public function enderecoSave(Request $request)
    {
        $request->validate([
            'post_code' => 'required',
            'state'     => 'required',
            'city'      => 'required',
            'address'   => 'required',
            'address2'  => 'required',
            'number'    => 'required',
            'phone2'    => 'required',
        ]);

        $addresses['user_id']       = auth()->user()->id;
        $addresses['post_code']     = $request->post_code;
        $addresses['state']         = $request->state;
        $addresses['city']          = $request->city;
        $addresses['address2']      = $request->address2;
        $addresses['address']       = $request->address;
        $addresses['number']        = $request->number;
        $addresses['complement']    = $request->complement;
        $addresses['phone1']        = $request->phone1;
        $addresses['phone2']        = $request->phone2;

        if($request->id){
            Address::where('id', $request->id)->update($addresses);
            return redirect()->back()->with('success', 'Endereço Atualizado!');
        }else{
            Address::create($addresses);
            return redirect()->back()->with('success', 'Endereço Salvo!');
        }
    }

    public function apagarEndereco($id)
    {
        $address = Address::find($id);

        $address->delete();

        return redirect('perfil')->with('destroy', 'Endereço Excluido!');
    }

    // Solicitação para ser afiliado
    public function perfilAfiliadoRequest(Request $request)
    {
        $par['user_id'] = auth()->user()->id;
        $par['bank_code'] = $request->bank_code;
        $par['agencia'] = $request->agencia;
        $par['agencia_dv'] = $request->agencia_dv;
        $par['conta'] = $request->conta;
        $par['conta_dv'] = $request->conta_dv;
        $par['type'] = $request->type;
        $par['cnpj_cpf'] = $request->cnpj_cpf;
        $par['legal_name'] = $request->legal_name;

        RequestAfiliado::create($par);

        return redirect()->back()->with('success', 'Sua solicitação foi enviado com sucesso, so aguarda que logo será liberado!');
    }

    public function confirmaAfiliado(Request $request)
    {
        // \Log::info($request->all());
        $collectSendRecipients = [
            'api_key' => ENV('PAGARME_API_KEY'),
            // message: "ERROR TYPE: invalid_parameter. PARAMETER: external_id. MESSAGE: External ID must be unique"
            'external_id' => md5($request->user_id.$request->get('cnpj_cpf')),

            // a regra de transferência é no primeiro dia útil do mês
            'transfer_interval' => 'monthly',
            'transfer_day' => 1,
            'transfer_enabled' => true,

            'bank_account' => [
                'bank_code' => $request->get('bank_code'),
                'agencia' => $request->get('agencia'),
                'agencia_dv' => empty($request->get('agencia_dv')) ? 0 : $request->get('agencia_dv'),
                'conta' => $request->get('conta'),
                'type' => $request->get('type'),
                'conta_dv' => $request->get('conta_dv'),
                'document_number' => $request->get('cnpj_cpf'),
                'legal_name' => $request->get('legal_name'),
            ]
        ];

        $post_pagarme_recipient = Http::post('https://api.pagar.me/1/recipients', $collectSendRecipients)->object();

        RequestAfiliado::where('user_id', $request->user_id)->delete();
        User::find($request->user_id)->update(['permission' => 2, 'recipient_id' => $post_pagarme_recipient->id]);

        return response()->json('', 200);
    }
}
