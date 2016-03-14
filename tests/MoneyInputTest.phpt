<?php

/**
 * @testCase
 */

namespace Achse\MoneyInput\Tests;

require __DIR__ . '/bootstrap.php';

use Achse\MoneyInput\MoneyInput;
use Kdyby\Money\Currency;
use Kdyby\Money\Money;
use Nette\InvalidArgumentException;
use Tester\Assert;
use Tester\TestCase;



class DateTimeInputTest extends TestCase
{

	public function testSimple()
	{
		$currency = new Currency('CZK');

		$input = new MoneyInput($currency, 'caption');
		Assert::null($input->getValue());

		$amount = Money::fromFloat(100, $currency);
		$input->setValue($amount);
		$this->assertMoney($amount, $input);

		$input->setValue('');
		Assert::equal(NULL, $input->getValue());

		$input->setValue(NULL);
		Assert::equal(NULL, $input->getValue());
	}



	public function testSetDefaultValue()
	{
		$currency = new Currency('CZK');

		$input = new MoneyInput($currency, 'caption');

		Assert::exception(
			function () use ($input) {
				$input->setDefaultValue('Invalid Type');
			},
			InvalidArgumentException::class,
			'As default value, Money object must be given. \'string\' given instead'
		);

		$amount = Money::fromFloat(100, $currency);
		$input = new MoneyInput($currency, 'caption');
		$input->setDefaultValue($amount);

		Assert::notSame($currency, $input->getValue());
		$this->assertMoney($amount, $input->getValue());
	}



	/**
	 * @param Money $expected
	 * @param Money $given
	 */
	public function assertMoney(Money $expected, Money $given)
	{
		Assert::equal($expected->toFloat(), $given->toFloat());
		Assert::equal($expected->getCurrency()->getCode(), $given->getCurrency()->getCode());
	}

}



(new DateTimeInputTest())->run();
