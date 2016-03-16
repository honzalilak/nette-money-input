<?php

/**
 * @testCase
 */

namespace Achse\MoneyInput\Tests;

require __DIR__ . '/bootstrap.php';

use Achse\MoneyInput\ICurrencyFinder;
use Achse\MoneyInput\MoneyInput;
use Kdyby\Money\Currency;
use Kdyby\Money\Money;
use Mockery;
use Mockery\MockInterface;
use Nette\InvalidArgumentException;
use Tester\Assert;
use Tester\TestCase;



class DateTimeInputTest extends TestCase
{

	const DUMMY_CURRENCY_OPTIONS = ['CZK' => 'CZK'];



	public function testSimple()
	{
		$input = $this->moneyInputBuilder();

		Assert::null($input->getValue());

		$amount = Money::fromFloat(100, Currency::get('CZK'));
		$input->setValue($amount);
		$this->assertMoney($amount, $input->getValue());

		$input->setValue('');
		Assert::equal(NULL, $input->getValue());

		$input->setValue(NULL);
		Assert::equal(NULL, $input->getValue());
	}



	public function testSetDefaultValue()
	{
		$currency = new Currency('CZK');

		$input = $this->moneyInputBuilder();

		Assert::exception(
			function () use ($input) {
				$input->setDefaultValue('Invalid Type');
			},
			InvalidArgumentException::class,
			'As default value, Money object must be given. \'string\' given instead'
		);

		$amount = Money::fromFloat(100, $currency);
		$input = $this->moneyInputBuilder();
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



	/**
	 * @return ICurrencyFinder|MockInterface
	 */
	private function mockCurrencyFinder()
	{
		return Mockery::mock(ICurrencyFinder::class)
			->shouldReceive('findByCode')->andReturnUsing(
				function ($code) {
					return Currency::get($code);
				}
			)->getMock();
	}



	/**
	 * @return MoneyInput
	 */
	public function moneyInputBuilder()
	{
		$input = new MoneyInput(
			'caption',
			MoneyInput::AMOUNT_LENGTH_LIMIT,
			self::DUMMY_CURRENCY_OPTIONS,
			$this->mockCurrencyFinder()
		);

		return $input;
	}

}



(new DateTimeInputTest())->run();
