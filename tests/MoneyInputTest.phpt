<?php

/**
 * @testCase
 */

namespace Achse\MoneyInput\Tests;

require __DIR__ . '/bootstrap.php';

use Kdyby\Money\Currency;
use Kdyby\Money\Money;
use Mockery;
use Nette\Forms\Form;
use Nette\InvalidArgumentException;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;



class MoneyInputTest extends TestCase
{

	const HAS_ERRORS = TRUE;
	const NO_ERRORS = FALSE;

	/**
	 * @var MoneyInputMocker
	 */
	private $mocker;



	public function __construct()
	{
		$this->mocker = new MoneyInputMocker();
	}



	public function testControlPrototypesInitialized()
	{
		$input = $this->mocker->moneyInputBuilder();
		Assert::type(Html::class, $input->getAmountControlPrototype());
		Assert::type(Html::class, $input->getCurrencyControlPrototype());
	}



	public function testGetControl()
	{
		$form = new Form();
		$input = $this->mocker->moneyInputBuilder();
		$form['money'] = $input;

		$expected = '<div class="row moneyInputControlContainer"><div class="col-sm-9 moneyInputAmountContainer">'
			. '<input name="money[amount]" id="frm-money" value="" class="money-input form-control">'
			. '</div><div class="col-sm-3 moneyInputCurrencyContainer">'
			. '<select name="money[currencyCode]" class="form-control"><option value="CZK">CZK</option></select>'
			. '</div></div>';

		Assert::equal($expected, (string) $input->getControl());
	}



	/**
	 * @dataProvider getDataForIsEmpty
	 *
	 * @param bool $expected
	 * @param string $rawAmount
	 * @param string $rawCurrency
	 */
	public function testIsEmpty($expected, $rawAmount, $rawCurrency)
	{
		$input = $this->mocker->getInputWithMockedUserInput($rawAmount, $rawCurrency);
		$input->loadHttpData();

		Assert::equal($expected, $input->isEmpty());
	}



	/**
	 * @return array
	 */
	public function getDataForIsEmpty()
	{
		return [
			[TRUE, '', ''],
			[TRUE, '0', 'CZK'],
			[TRUE, '100', ''],

			[FALSE, '100', 'CZK'],
			[FALSE, 'Trololoooo-oo-o-ooo', 'CZK'],
		];
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
		$input = $this->mocker->getInputWithMockedUserInput($rawAmount, $rawCurrency);

		$input->loadHttpData();

		/** @var float $amountInnerValue */
		$amountInnerValue = $this->mocker->getInnerInputValue($input, 'rawAmount');
		Assert::equal($expectedAmount, $amountInnerValue);

		/** @var string $currencyCodeInnerValue */
		$currencyCodeInnerValue = $this->mocker->getInnerInputValue($input, 'rawCurrencyCode');
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
		$input = $this->mocker->getInputWithMockedUserInput($rawAmount, $rawCurrency);

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
	 * @dataProvider getDataForValidateRange
	 *
	 * @param bool $expectedHasErrors
	 * @param string $rawAmount
	 * @param string $rawCurrency
	 * @param float|NULL $left
	 * @param float|NULL $right
	 */
	public function testValidateRange($expectedHasErrors, $rawAmount, $rawCurrency, $left, $right)
	{
		$input = $this->mocker->getInputWithMockedUserInput($rawAmount, $rawCurrency);

		$input->addRule(Form::RANGE, 'bla-bla', [$left, $right]);
		$input->loadHttpData();
		$input->validate();

		Assert::equal($expectedHasErrors, $input->hasErrors());
	}



	/**
	 * @return array
	 */
	public function getDataForValidateRange()
	{
		return [
			[self::NO_ERRORS, '', '', NULL, NULL],
			[self::NO_ERRORS, '', '', 0, 0],

			[self::HAS_ERRORS, '', '', -5, -10],
			[self::HAS_ERRORS, '', '', 5, 10],

			[self::NO_ERRORS, '100', 'CZK', NULL, NULL],
			[self::NO_ERRORS, '0', 'CZK', 0, 0],
			[self::NO_ERRORS, '100', 'CZK', 0, NULL],
			[self::NO_ERRORS, '-100', 'CZK', NULL, 0],
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
		$input = $this->mocker->moneyInputBuilder();
		Assert::false($input->isFilled());
		$input->setValue('');
		Assert::false($input->isFilled());

		$amount = Money::fromFloat(100, Currency::get('CZK'));
		$input->setDefaultValue($amount);
		Assert::true($input->isFilled());
	}



	/**
	 * @dataProvider getDataForGetValues
	 *
	 * @param float|NULL $expectedAmount
	 * @param string|NULL $expectedCurrency
	 * @param string $rawAmount
	 * @param string $rawCurrency
	 */
	public function testGetValues($expectedAmount, $expectedCurrency, $rawAmount, $rawCurrency)
	{
		$input = $this->mocker->getInputWithMockedUserInput($rawAmount, $rawCurrency);
		$input->loadHttpData();
		$result = $input->getValue();

		if ($expectedAmount === NULL || $expectedCurrency === NULL) {
			Assert::null($result);
		} else {
			MoneyAssertions::assertMoney(Money::fromFloat($expectedAmount, Currency::get($expectedCurrency)), $result);
		}
	}



	/**
	 * @return array
	 */
	public function getDataForGetValues()
	{
		return [
			[100000000, 'CZK', '100 000 000', 'CZK'],
			[0, 'CZK', '0', 'CZK'],

			[0, 'CZK', '', 'CZK'],
			[NULL, NULL, ' - - - ', '  oi! '],
		];
	}



	public function testSetDefaultValue()
	{
		$currency = new Currency('CZK');

		$input = $this->mocker->moneyInputBuilder();

		Assert::exception(
			function () use ($input) {
				$input->setDefaultValue('Invalid Type');
			},
			InvalidArgumentException::class,
			'As default value, Money object must be given. \'string\' given instead'
		);

		$amount = Money::fromFloat(100, $currency);
		$input = $this->mocker->moneyInputBuilder();
		$input->setDefaultValue($amount);

		Assert::notSame($currency, $input->getValue());
		MoneyAssertions::assertMoney($amount, $input->getValue());
	}

}



(new MoneyInputTest())->run();
