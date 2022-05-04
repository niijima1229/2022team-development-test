<?php

namespace App\Http\Controllers;

// paypay関係
use PayPay\OpenPaymentAPI\Client;
use PayPay\OpenPaymentAPI\Models\OrderItem;
use PayPay\OpenPaymentAPI\Models\CreateQrCodePayload;

use App\Models\PayPay;
use App\Models\Settlement;

use Illuminate\Http\Request;

class PayPayController extends Controller
{
    public function index()
    {
        // .envファイルに書いておく
        $client = new Client([
            'API_KEY' => env('PAYPAY_API_KEY'),
            'API_SECRET' => env('PAYPAY_API_SECRET'),
            'MERCHANT_ID' => env('PAYPAY_MERCHANT_ID')
        ], false);

        // paypayの支払いサイトが完了したら、リダイレクトされるURL
        // ブラウザの戻るボタンで戻っても、支払いIDが決済完了になっているので３秒後にリダイレクトされ直すだけ
        $rediect_url = 'http://localhost/thanks';

        //-------------------------------------
        // 商品情報を生成する
        //-------------------------------------
        $items = (new OrderItem())
            ->setName('通話チケット')
            ->setQuantity(1)
            ->setUnitPrice(['amount' => 100, 'currency' => 'JPY']);

        //-------------------------------------
        // QRコードを生成する
        //-------------------------------------
        $payload = new CreateQrCodePayload();
        $payload->setOrderItems($items);
        $payload->setMerchantPaymentId("mpid_" . rand());    // 同じidを使いまわさないこと！
        $payload->setCodeType("ORDER_QR");
        $payload->setAmount(["amount" => 100, "currency" => "JPY"]);
        $payload->setRedirectType('WEB_LINK');
        $payload->setIsAuthorization(false);
        $payload->setRedirectUrl($rediect_url);
        $payload->setUserAgent($_SERVER['HTTP_USER_AGENT']);
        $QRCodeResponse = $client->code->createQRCode($payload);
        if ($QRCodeResponse['resultInfo']['code'] !== 'SUCCESS') {
            echo ("QRコード生成エラー");
            return;
        }

        $settlement = Settlement::firstOrNew(['paypay_settlement_id' => $QRCodeResponse['data']['merchantPaymentId']]);
        $settlement->user_id = 1;
        $settlement->paypay_settlement_id = $QRCodeResponse['data']['merchantPaymentId'];
        $settlement->save();

        // paypayの支払いページに行く。支払いが終わったら$payload->setRedirectUrlにリダイレクトされる
        return redirect($QRCodeResponse['data']['url']);

        // var_dump('$QRCodeResponse');
        // 支払いIDはデータベースに保存しておく
        $merchantPaymentId = $QRCodeResponse['data']['merchantPaymentId'];
        Log::info('paypay決済ID', $merchantPaymentId);

        // var_dump($QRCodeResponse);

        //-------------------------------------
        // 決済情報を取得する
        //-------------------------------------
        $QRCodeDetails = $client->payment->getPaymentDetails($merchantPaymentId);
        if ($QRCodeDetails['resultInfo']['code'] !== 'SUCCESS') {
            echo ("決済情報取得エラー");
            return;
        }
        Log::info('paypay決済情報', $QRCodeDetails);
    }

    public function thanks()
    {
        PayPay::polling();
        return 'paypay支払い完了！<br><a href="home">戻る</a>';
    }
}
