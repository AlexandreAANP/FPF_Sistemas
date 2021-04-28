<?php
namespace App\Functions;

use Money\Currencies\ISOCurrencies;
use Money\Money;
use Money\Currency;
use Money\Parser\DecimalMoneyParser;

class MoneyParser
{
	private $decimalMoneyParser;

	public function __construct()
	{		
		$this->decimalMoneyParser = new DecimalMoneyParser(new ISOCurrencies()); 
	}
	
	public function parse($value, $currency = 'EUR')
	{
		return $this->decimalMoneyParser->parse($value, $currency);
	}
	
	public function parseMoney($value, $currency = 'EUR')
	{
		//return $this->decimalMoneyParser->parse($value, $currency);
		return new Money($value, new Currency($currency));
	}	
}
