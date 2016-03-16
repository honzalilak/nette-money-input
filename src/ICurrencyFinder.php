<?php

namespace Achse\MoneyInput;

use Kdyby\Money\Currency;



interface ICurrencyFinder
{

	/**
	 * @param string $code
	 * @return Currency
	 * @throws CurrencyNotFoundException
	 */
	public function findByCode($code);

}
