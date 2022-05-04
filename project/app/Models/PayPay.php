<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// paypay関係
use PayPay\OpenPaymentAPI\Client;
use PayPay\OpenPaymentAPI\Models\OrderItem;
use PayPay\OpenPaymentAPI\Models\CreateQrCodePayload;

use App\Models\Settlement;

class PayPay extends Model
{
    use HasFactory;

    public static function polling()
    {
        $client = new Client([
            'API_KEY' => env('PAYPAY_API_KEY'),
            'API_SECRET' => env('PAYPAY_API_SECRET'),
            'MERCHANT_ID' => env('PAYPAY_MERCHANT_ID')
        ], false);

        if (Settlement::where('user_id', 1)->where('is_paid', false)->exists()) {
            $settlement = Settlement::where('user_id', 1)->where('is_paid', false)->first();
            $merchantPaymentId = $settlement->paypay_settlement_id;
            $QRCodeDetails = $client->payment->getPaymentDetails($merchantPaymentId);
            if ($QRCodeDetails['resultInfo']['code'] !== 'SUCCESS') {
                echo ("決済情報取得エラー");
                return;
            }
            if($QRCodeDetails['data']['status'] == 'COMPLETED') {
                $settlement->is_paid = true;
                $settlement->save();
                PayPay::ticket();
            }
        }
        return;
    }

    public static function ticket()
    {
        return var_dump('購入ありがとう');
    }
}
