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
use Nette\Forms\Form;
use Nette\InvalidArgumentException;
use ReflectionObject;
use Tester\Assert;
use Tester\TestCase;



class MoneyInputTest extends TestCase
{

	const DUMMY_CURRENCY_OPTIONS = ['CZK' => 'CZK'];

	const HAS_ERRORS = TRUE;
	const NO_ERRORS = FALSE;



	public function testGetControl()
	{
		$form = new Form();
		$input = $this->moneyInputBuilder();
		$form['money'] = $input;

		$expected = '<input name="money[amount]" id="frm-money" value="" class="money-input">'
			. '<select name="money[currencyCode]"><option value="CZK">CZK</option></select>';

		Assert::equal($expected, (string) $input->getControl());
	}



	/**
	 * @dataProvider getDataForTestLoadHttpData
	 *
	 * @param float $expectedAmount
	 * @param string $expectedCurrencyCode
	 * @param string $rawAmount
	 * @param string $rawCurrency
	 */
	public function testLoadHttpData($expectedAmount, $expectedCurrencyCode, $rawAmount, $rawCurrency)
	{
		$input = $this->getInputWithMockedUserInput($rawAmount, $rawCurrency);

		$input->loadHttpData();

		/** @var float $amountInnerValue */
		$amountInnerValue = $this->getInnerInputValue($input, 'rawAmount');
		Assert::equal($expectedAmount, $amountInnerValue);

		/** @var string $currencyCodeInnerValue */
		$currencyCodeInnerValue = $this->getInnerInputValue($input, 'rawCurrencyCode');
		Assert::equal($expectedCurrencyCode, $currencyCodeInnerValue);
	}



	/**
	 * @dataProvider getDataForValidate
	 *
	 * @param bool $expectedHasErrors
	 * @param string $rawAmount
	 * @param string $rawCurrency
	 */
	public function testValidate($expectedHasErrors, $rawAmount, $rawCurrency)
	{
		$input = $this->getInputWithMockedUserInput($rawAmount, $rawCurrency);

		$input->setRequired('Filed is required.');
		$input->loadHttpData();
		$input->validate();

		Assert::equal($expectedHasErrors, $input->hasErrors());
	}



	/**
	 * @return array
	 */
	public function getDataForValidate()
	{
		return [
			[self::HAS_ERRORS, '', ''],
			[self::HAS_ERRORS, '-', 'CZK'],
			[self::HAS_ERRORS, '100-', 'CZK'],

			[self::NO_ERRORS, '10.50', 'CZK'],
			[self::NO_ERRORS, '100', 'CZK'],
			[self::NO_ERRORS, '100 000', ' CZK '],
		];
	}



	/**
	 * @return array
	 */
	public function getDataForTestLoadHttpData()
	{
		return [
			['100000000', 'CZK', '100 000 000', 'CZK'],
			['', 'CZK', '', 'CZK'],
			['0', 'CZK', '0', 'CZK'],

			['---', 'oi!', ' - - - ', '  oi! '],
		];
	}



	public function testIsFilled()
	{
		$input = $this->moneyInputBuilder();
		Assert::false($input->isFilled());
		$input->setValue('');
		Assert::false($input->isFilled());

		$amount = Money::fromFloat(100, Currency::get('CZK'));
		$input->setDefaultValue($amount);
		Assert::true($input->isFilled());
	}



	public function testGetValues()
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
	private function moneyInputBuilder()
	{
		$input = new MoneyInput(
			'caption',
			MoneyInput::AMOUNT_LENGTH_LIMIT,
			self::DUMMY_CURRENCY_OPTIONS,
			$this->mockCurrencyFinder()
		);

		return $input;
	}



	/**
	 * @param MoneyInput $input
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getInnerInputValue(MoneyInput $input, $propertyName)
	{
		$reflection = new ReflectionObject($input);
		$property = $reflection->getProperty($propertyName);
		$property->setAccessible(TRUE);

		return $property->getValue($input);
	}



	/**
	 * @param string $rawAmount
	 * @param string $rawCurrency
	 * @return MoneyInput
	 */
	public function getInputWithMockedUserInput($rawAmount, $rawCurrency)
	{
		/** @var Form|MockInterface $form */
		$form = Mockery::mock(new Form());
		$form->shouldReceive('getHttpData')
			->with(Form::DATA_LINE, 'money[amount]')->andReturn($rawAmount);
		$form->shouldReceive('getHttpData')
			->with(Form::DATA_LINE, 'money[currencyCode]')->andReturn($rawCurrency)->getMock();

		$input = $this->moneyInputBuilder();
		$input->setParent($form, 'money');

		return $input;
	}

}



(new MoneyInputTest())->run();
