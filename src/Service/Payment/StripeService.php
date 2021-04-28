<?php

namespace App\Service\Payment;

use Exception;
use Stripe\Exception\ApiErrorException;
use \Stripe\StripeClient;

class StripeService
{
	/**
	 *Create line Items
	 *@param $secretKey String, $product Array
	 *@return lineItem Array
	 **/
	public function createLineItem($secretKey, $arProduct)
	{	
		try {
			\Stripe\Stripe::setApiKey($secretKey);

			$objProduct = \Stripe\Product::create([
				'name' => $arProduct['name'],
				'type' => 'good',
			]);

			$lineItem = [
				'price_data' => [
					'product' => $objProduct->id,
					'unit_amount' => $arProduct['unit_amount'],
					'currency' => $arProduct['currency'],
				],
				'quantity' => $arProduct['quantity'],
			];
			
			return $lineItem;

		} catch (ApiErrorException $e) {
			$arError = [
				"status" => $e->getHttpStatus(),
				"type" => $e->getError()->type,
				"code" => $e->getError()->code,
				"param" => $e->getError()->param,
				"message" => $e->getError()->message,
			];
			$strError = json_encode($arError);          
			http_response_code($e->getHttpStatus());
			echo $strError;
			exit();
		}
		catch (Exception $e) {
			echo($e);
			exit();
		}
	}

	/**
	 *Create Coupons
	 *@param $secretKey String, $coupon Array
	 *@return CouponId integer
	 **/
	public function createCoupon($secretKey, $arCoupon)
	{			
		try {
			\Stripe\Stripe::setApiKey($secretKey);

			$objProduct = \Stripe\Coupon::create([
				'name' => $arCoupon['name'],
				$arCoupon['type'] => $arCoupon['value'],
				'duration' => $arCoupon['duration'],
				'currency' => $arCoupon['currency'],
			]);

			return $objProduct->id;

		} catch (ApiErrorException $e) {
			$arError = [
				"status" => $e->getHttpStatus(),
				"type" => $e->getError()->type,
				"code" => $e->getError()->code,
				"param" => $e->getError()->param,
				"message" => $e->getError()->message,
			];
			$strError = json_encode($arError);          
			http_response_code($e->getHttpStatus());
			echo $strError;
			exit();
		}
		catch (Exception $e) {
			echo($e);
			exit();
		}
	}

	/**
	 *Create line Items
	 *@param $secretKey String, $lineItems Array, $paymentItentDescription String, $stripeCustomizedUrls Array,
	 *@return Session Obj
	 **/
	public function createSession($secretKey, $lineItems, $paymentItentDescription, $stripeCustomizedUrls, $couponId)
	{	
		try {
			\Stripe\Stripe::setApiKey($secretKey);

			$session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' =>  $lineItems,

                'payment_intent_data' => [
                    'description' => $paymentItentDescription,
					//'transfer_group' => $paymentItentDescription,
                    // Usar isso se quiser passar o dinheiro para Connected Account
                    // 'application_fee_amount' => 18, // Valor do desconto pelo serviço
                    // 'transfer_data' => ['destination' => $stripeConnectedAccount],
                ],

                'mode' => 'payment',
				'discounts' => [[
					'coupon' => $couponId,
				]],
                'success_url' => $stripeCustomizedUrls['success'],
                'cancel_url' => $stripeCustomizedUrls['cancel'],
            ]);
			
			return $session;

		} catch (ApiErrorException $e) {
			$arError = [
				"status" => $e->getHttpStatus(),
				"type" => $e->getError()->type,
				"code" => $e->getError()->code,
				"param" => $e->getError()->param,
				"message" => $e->getError()->message,
			];
			$strError = json_encode($arError);          
			http_response_code($e->getHttpStatus());
			echo $strError;
			exit();
		}
		catch (Exception $e) {
			echo($e);
			exit();
		}
	}

	/**
	 *Retrieve payment log
	 *@param $secretKey String, $stripeSessionId String
	 *@return PaymentItent Array
	 **/
	public function retrievePaymentItent($secretKey, $stripeSessionId)
	{	
		try {
			$stripe = new \Stripe\StripeClient($secretKey);
			$objSession = $stripe->checkout->sessions->retrieve(
				$stripeSessionId,
				[]
			);
			$paymentLog = $stripe->paymentIntents->retrieve(
				$objSession->payment_intent,
				[]
			);

			return $paymentLog;
		} catch (ApiErrorException $e) {
			$arError = [
				"status" => $e->getHttpStatus(),
				"type" => $e->getError()->type,
				"code" => $e->getError()->code,
				"param" => $e->getError()->param,
				"message" => $e->getError()->message,
			];
			$strError = json_encode($arError);          
			http_response_code($e->getHttpStatus());
			echo $strError;
			exit();
		}
		catch (Exception $e) {
			echo($e);
			exit();
		}
	}
}
