<?php
namespace App\Functions;

class Currency
{
    public function formatMoney($price) { // Format the price for saving on database ------------------------------------ //
        $price = preg_replace('/[^0-9.,]/i','', $price);

        if ($pv = strpos($price, ',')) {
            if ($pp = strpos($price, '.')) {
                if ($pv < $pp) {
                    $price = str_replace(',', '', $price);
                } else if ($pv > $pp) {
                    $price = str_replace(',', '#', $price);
                    $price = str_replace('.', '', $price);
                    $price = str_replace('#', '.', $price);
                }

                $priceNumber = preg_replace('/[^0-9]/i','', $price);
                if (strlen($price) - strlen($priceNumber) > 1) {
                    return 'error:1';
                }
            } else {
                $priceNumber = preg_replace('/[^0-9]/i','', $price);
                if (strlen($price) - strlen($priceNumber) > 1) {
                    return 'error:2';
                } else {
                    $price = str_replace(',', '.', $price);
                }
            }
        }
        return $price;
    }

    public function round($price) {
        //$price = 43.15625478; // Para testar e ver se está arredondando pra cima ou pra baixo
        $price = number_format($price, 3);

        $priceStr = strval($price);
        $priceA = substr($priceStr, 0, strrpos($priceStr, '.'));
        $priceB = substr($priceStr, strrpos($priceStr, '.') + 1);
        $price2 = substr($priceB, 0, -1);
        $price3 = substr($priceB, -1);
        if ($price3 > 5) {
            $price2 = $price2 + 1;
        }

        $price = $priceA . '.' . $price2;

        return $price;
    }
}
