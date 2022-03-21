<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPayment extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $order;
    public $reason;
    public $send_email;
    public function __construct($order, $reason, $send_email)
    {
        $this->order = $order;
        $this->reason = $reason;
        $this->send_email = $send_email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $assunto = 'Compra Aprovada';
        switch($this->send_email){
            case 'comprador':
                if($this->reason == 'canceled'){
                    $msg1 = 'caro cliente '.$this->order->user_name.' infelizmente não foi aprovado seu pagamento, verifique o credenciador do banco ou utilize outro cartão.';
                    $assunto = 'Compra Não Aprovada';
                }else{
                    $msg1 = 'caro cliente '.$this->order->user_name.' seu pagamento foi aprovado e seu pedido está em processo de montagem.';
                }
            break;
            case 'afiliado':
                $msg1 = 'Compra não aprovado para o cliente '.$this->order->user_name.' do Pedido - '.$this->order->order_number;
            break;
            case 'preservando':
                $msg1 = 'Compra não aprovado para o cliente '.$this->order->user_name.' do Pedido - '.$this->order->order_number;
            break;
        }

        return $this->markdown('emails.orderPayments')->with([
            'orders' => $this->order,
            'msg1' => $msg1,
        ])->subject($assunto);
    }
}
