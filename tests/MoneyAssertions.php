<?php

namespace Achse\MoneyInput\Tests;

use Kdyby\Money\Money;
use Tester\Assert;



class MoneyAssertions
{

	/**
	 * @param Money $expected
	 * @param Money $given
	 */
	public static function assertMoney(Money $expected, Money $given)
	{
		Assert::equal($expected->toFloat(), $given->toFloat());
		Assert::equal($expected->getCurrency()->getCode(), $given->getCurrency()->getCode());
	}

}
