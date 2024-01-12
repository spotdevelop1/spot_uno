<?php

namespace App\Conekta;
use Conekta\Conekta;
use App\Reference;
use App\Pay;
use App\Ethernetpay;
use App\Number;
use Http;

class ConektaPayment{

    function __construct(){
        // KEY de Prueba
        Conekta::setApiKey(env('CONECTA_TEST_KEY'));
        // KEY de Producción
        //Conekta::setApiKey(env('CONECTA_KEY'));
        Conekta::setApiVersion('2.0.0');
    }

    function createOrderConekta($request){
        $amount = $request->amount;
        $description = $request->description;
        // $myproduct = $request->myproduct;
        // $email = $request->email;
        // $cel_destiny_reference = $request->cel_destiny_reference;
        // $amount = $request->amount;
        // $quantity = $request->quantity;
        // $concepto = $request->concepto;
        // $type = $request->type;
        // $channel = $request->channel;
        // $user = $request->user_id;
        // $client = $request->client_id;
        // $pay_id = $request->pay_id;
        // $amount = $amount*100;

        // if($type == 1 || $type == 4 || $type == 5){
        //     $number_id = $request->number_id;
        //     $offerID = $request->offer_id;
        //     $rate = $request->rate_id;
        // }else if($type == 2){
        //     $pack_id = $request->pack_id;
        // }
        
        $line_items = [];
        $line_items[0]['name'] = "SPOT UNO";
        $line_items[0]['unit_price'] = $amount;
        $line_items[0]['currency'] = "MXN";
        $line_items[0]['quantity'] = $amount;

        $expires_at = (new \DateTime())->add(new \DateInterval('P5D'))->getTimestamp();
        $charges[0]["payment_method"]["type"] = 'oxxo_cash';
        $charges[0]["payment_method"]["expires_at"] = $expires_at;
        $charges[0]["description"] = $description;

        $order['line_items'] = $line_items;
        $order['currency'] = 'MXN';
        $order['customer_info']['name'] = 'Lalo Reyes';
        $order['customer_info']['email'] = 'l.eduardo.reyesrodriguez@gmail.com';
        $order['customer_info']['phone'] = '9611840957';
        // $order['customer_info']['name'] = $name." ".$lastname;
        // $order['customer_info']['email'] = $email;
        // $order['customer_info']['phone'] = $cel_destiny_reference;
        $order['charges'] = $charges;

        try{
            $conekta_order = \Conekta\Order::create($order);

            if(isset($conekta_order->id)){
               
                return $conekta_order;
            }
        }catch(\Conekta\ParameterValidationError $error){
            $err['code'] = $error->getCode();
            $err['message'] = $error->getMessage();
            return $err;
        }catch(\Conekta\Handler $error){
            $err['code'] = $error->getCode();
            $err['message'] = $error->getMessage();
            return $err;
        }

        // $conekta_order = \Conekta\Order::create($order);

        // return $conekta_order;


    }

    public function createPaymentLink($request){
        $pack_id = $request['pack_id'];
        if($pack_id == null){
            $request['pack_id'] = 0;
        }
        try{
            $conekta_checkout = \Conekta\Checkout::create([
                'name' => $request['concepto'],
                'type' => "PaymentLink",
                'recurrent' => false,
                'expires_at' => (new \DateTime())->add(new \DateInterval('P5D'))->getTimestamp(),
                'allowed_payment_methods' => ["card"],
                'needs_shipping_contact' => false,
                'monthly_installments_enabled' => true,
                'monthly_installments_options' => [3, 6, 9, 12],
                'order_template' => [
                    'line_items' => [[
                    'name' => $request['concepto'],
                    'unit_price' => $request['amount']*100,
                    'quantity' => 1
                    ]],
                    'currency' => "MXN",
                    'customer_info' => [
                    'name' => $request['name'].' '.$request['lastname'],
                    'email' => $request['email'],
                    'phone' => $request['cel_destiny_reference']
                    ],
                    'metadata' => [
                        'pay_id' => $request['pay_id'],
                        'client_id' => $request['client_id'],
                        'referencestype_id' => $request['type'],
                        'offer_id' => $request['offer_id'],
                        'number_id' => $request['number_id'],
                        'rate_id' => $request['rate_id'],
                        'user_id' => $request['user_id'],
                        'pack_id' => $request['pack_id']
                    ]
                ]
            ]);
            return $conekta_checkout;

        }catch(\Conekta\ParameterValidationError $error){
            $err['code'] = $error->getCode();
            $err['message'] = $error->getMessage();
            return $err;
        }catch(\Conekta\Handler $error){
            $err['code'] = $error->getCode();
            $err['message'] = $error->getMessage();
            return $err;
        }
    }

    public function createPaymentLinkAllMethods($request){
        // return $request;
        $pack_id = $request['pack_id'];
        $channel_system = $request['channel_system'];
        $method = $request['method'];
        
        if($pack_id == null){
            $request['pack_id'] = 0;
        }

        if($channel_system == null){
            $request['channel_system'] = 0;
        }

        if($method == null){
            $methods = ["card","cash","bank_transfer"];
        }else{
            $methods = [$method];
        }
        if($channel_system == "mi_altcel"){
            $chann = false;
        }else{
            $chann = true;
        }
        try{
            $conekta_checkout = \Conekta\Checkout::create([
                'name' => $request['concepto'],
                'type' => "PaymentLink",
                'recurrent' => false,
                'expires_at' => (new \DateTime())->add(new \DateInterval('P5D'))->getTimestamp(),
                'allowed_payment_methods' => $methods,
                'needs_shipping_contact' => false,
                'monthly_installments_enabled' => $chann,
                'monthly_installments_options' => [3, 6, 9, 12],
                'order_template' => [
                    'line_items' => [[
                    'name' => $request['concepto'],
                    'unit_price' => $request['amount']*100,
                    'quantity' => 1
                    ]],
                    'currency' => "MXN",
                    'customer_info' => [
                    'name' => $request['name'].' '.$request['lastname'],
                    'email' => $request['email'],
                    'phone' => $request['cel_destiny_reference']
                    ],
                    'metadata' => [
                        'pay_id' => $request['pay_id'],
                        'client_id' => $request['client_id'],
                        'referencestype_id' => $request['type'],
                        'offer_id' => $request['offer_id'],
                        'number_id' => $request['number_id'],
                        'rate_id' => $request['rate_id'],
                        'user_id' => $request['user_id'],
                        'pack_id' => $request['pack_id'],
                        'channel_system' => $request['channel_system']
                    ]
                ]
            ]);
            return $conekta_checkout;

        }catch(\Conekta\ParameterValidationError $error){
            $err['code'] = $error->getCode();
            $err['message'] = $error->getMessage();
            return $err;
        }catch(\Conekta\Handler $error){
            $err['code'] = $error->getCode();
            $err['message'] = $error->getMessage();
            return $err;
        }
    }

    public function createCustomer($request){
        $name = $request['name'];
        $email = $request['email'];
        $amount = $request['amount'];
        $concepto = $request['concepto'];

        // KEY de Prueba
        // Conekta::setApiKey(env('CONECTA_TEST_KEY'));
        // KEY de Producción
        Conekta::setApiKey(env('CONECTA_KEY'));
        Conekta::setApiVersion('2.0.0');

        $validCustomer = [
            'name' => $name,
            'email' => $email
          ];
          $customer = \Conekta\Customer::create($validCustomer);
          $response = ConektaPayment::createOrderEmpty($customer->id,$amount,$concepto);
          return $response;
    }

    public function createOrderEmpty($customerID,$amount,$concepto){
        $validOrderWithCheckout = array(
        'line_items'=> array(
            array(
            'name'=> $concepto,
            'description'=> 'Servicios Conecta',
            'unit_price'=> $amount*100,
            'quantity'=> 1,
            )
        ),
        'checkout' => array(
            'allowed_payment_methods' => array("card"),
            'monthly_installments_enabled' => false,
        ),
        'customer_info' => array(
            'customer_id'   =>  $customerID
        ),
        'currency'    => 'mxn'
        );
        $order = \Conekta\Order::create($validOrderWithCheckout);
        return $order;
    }
}
