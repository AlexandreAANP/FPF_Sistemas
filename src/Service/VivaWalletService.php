<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class VivaWalletService
{

  private $client_id; // Client ID, Provided by wallet
  private $client_secret; // Client Secret, Provided by wallet
  //private $url; // Url to make request, sandbox or live (sandbox APP_ENV=dev or test) (live APP_ENV=prod)
  private $merchant_id; //Merchant ID , Provided by wallet
  private $api_key; //Api Key, Provided by wallet
  private $headers; //Set the authorization to curl

  public function __construct(ParameterBagInterface $environment, $credentials){
    $this->client_id = $credentials['payment_gateway_viva_wallet_client_id'];
    $this->client_secret = $credentials['payment_gateway_viva_wallet_client_secret'];
    $this->merchant_id = $credentials['payment_gateway_viva_wallet_merchant_id'];
    $this->api_key = $credentials['payment_gateway_viva_wallet_api_key'];
    $this->headers = [];
    $this->url = $environment->get("kernel.environment") == 'prod' ? 'https://www.vivapayments.com' : 'https://demo.vivapayments.com';
    $this->headers[] = 'Authorization: Basic '.base64_encode($this->merchant_id.':'.$this->api_key);
    $this->headers[] = 'Content-Type: application/json';
  }

  /**
  Every payment on the Viva Wallet platform needs an associated payment order.
  A payment order is represented by a unique numeric orderCode.

  PaymentOrder data struture

  $po[
    'client_email' => 'client@mail.com', //string
    'client_phone' => '+351963963963', //string
    'client_fullname' => 'Client Name ', //string
    'payment_timeout' => 86400, // int Limit the payment period
    'invoice_lang' => 'pt-PT', //string  The invoice lang that the client sees
    'max_installments' => 0, //int
    'allow_recurring' => true, // Boolean
    'is_preauth' => false,  // Boolean false captures the amount, true waits to be captured manually on wallet
    'amount' => 675, // int value, 1 euro is 100
    'merchant_trns' => 'Booking:45646', // string
    'customer_trns' => 'Reserva #45645 ' // string
  ]
  **/

  /**
   * Set PaymentOrder
   * @param string $paymentOrder PaymentOrder
   * @return array
   */
  public function setPaymentOrder(string $paymentOrder){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->url.'/api/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $paymentOrder);

    if (curl_errno($ch)){
      $err = curl_error($ch);
      curl_close($ch);

        return [
          'status' => 0,
          'data' => $err,
          'redirect_url' => null
        ];
    }

    $result = curl_exec($ch);
    curl_close($ch);
    $paymetOrderResponse = json_decode($result);
  
      return [
        'status' => 1,
          'data' => $result,
          'redirect_url' => $this->url.'/web/checkout?ref='.$paymetOrderResponse->OrderCode
      ];
  }

   /**
   * A payment order is represented by a unique numeric
   * Get Transaction
   * @param string $transaction_id
   * @return array
   */
  public function getTransaction(string $transaction_id = null){

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_URL, $this->url.'/api/transactions/'.$transaction_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if (curl_errno($ch)){

      $err = curl_error($ch);
      curl_close($ch);
        return [
          'status' => 0,
          'data' => $err,
        ];
    }
    $result = curl_exec($ch);
    curl_close($ch);
    $e = json_decode($result);

    return [
        'status' => 1,
        'data' => $e
      ];
  }

}