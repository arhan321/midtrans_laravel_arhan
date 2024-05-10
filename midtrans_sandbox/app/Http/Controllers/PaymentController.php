<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $params = array (
            'transaction_details' => array (
                'order_id' => Str::uuid(),
                'gross_amount' => $request->price,
            ),
            'item_details' => array(
                array(
                    'price' => $request->price,
                    'quantity' => 1,
                    'name' => $request->item_name,
                )
            ),
            'customer_details' => array (
                'first_name' => $request->customer_first_name,
                'email' => $request->customer_email,
            ),
            'enabled_payments' => array ('credit_card', 'bca_va', 'bni_va', 'bri_va'),
        );
        $auth = base64_encode(env('MIDTRANS_SERVER_KEY'));

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Basic $auth",
        ])->post('https://app.sandbox.midtrans.com/snap/v1/transactions', $params);
        $response = json_decode($response->body());
        // Save to db
        $payment = new Payment();
        $payment->order_id = $params['transaction_details']['order_id'];
        $payment->status = 'pending';
        $payment->price = $request->price;
        $payment->customer_first_name = $request->customer_first_name;
        $payment->customer_email = $request->customer_email;
        $payment->item_name = $request->item_name;
        $payment->checkout_link = $response->redirect_url;
        $payment->save();

        return response()->json($response);
    }
    public function webhook(Request $request)
    {
        $auth = base64_encode(env('MIDTRANS_SERVER_KEY'));
        $responseHttp = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Basic $auth",
        ])->get("https://api.sandbox.midtrans.com/v2/{$request->order_id}/status");
    
        $response = json_decode($responseHttp->body());
    
        // Periksa di database
        $payment = Payment::where('order_id', $response->order_id)->firstOrFail();
    
        if ($payment->status === 'settlement' || $payment->status === 'capture') {
            return response()->json('Payment has been already processed');
        }
        
        if ($response->transaction_status === 'capture') {
            // Misal memasukkan atau mengirimkan link dari pembelian ke customer
            $payment->status = 'capture';
        } else if ($response->transaction_status === 'settlement') {
            // Misal memasukkan atau mengirimkan link dari pembelian ke customer
            $payment->status = 'settlement';
        } else if ($response->transaction_status === 'pending') {
            $payment->status = 'pending';
        } else if ($response->transaction_status === 'deny') {
            $payment->status = 'deny';
        } else if ($response->transaction_status === 'expire') {
            $payment->status = 'expire';
        } else if ($response->transaction_status === 'cancel') {
            $payment->status = 'cancel';
        }
        $payment->save();
        return response()->json('succes');
        
    }
}
