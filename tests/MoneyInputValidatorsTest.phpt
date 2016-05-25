<?php

/**
 * @testCase
 */

namespace Achse\MoneyInput\Tests;

require __DIR__ . '/bootstrap.php';

use Achse\MoneyInput\ICurrencyFinder;
use Achse\MoneyInput\MoneyInput;
use Achse\MoneyInput\MoneyInputValidators;
use Mockery;
use Mockery\MockInterface;
use Nette\Forms\Form;
use Tester\Assert;
use Tester\TestCase;



class MoneyInputValidatorsTest extends TestCase
{

	const DUMMY_CURRENCY_OPTIONS = ['CZK' => 'CZK'];


	/**
	 * @dataProvider getDataForValidateMoneyInputFilled
	 *
	 * @param bool $expected
	 * @param string $rawAmount
	 * @param string $rawCurrency
	 */
	public function testValidateMoneyInputValid($expected, $rawAmount, $rawCurrency)
	{
		$input = $this->getInputWithMockedUserInput($rawAmount, $rawCurrency);
		$input->loadHttpData();

		Assert::equal($expected, MoneyInputValidators::validateMoneyInputFilled($input));
	}



	/**
	 * @return array
	 */
	public function getDataForValidateMoneyInputFilled()
	{
		return [
			[FALSE, '', ''],
			[FALSE, '', 'CZK'],
			[FALSE, '100', ''],

			[TRUE, '0', 'CZK'],
			[TRUE, '100', 'CZK'],
			[TRUE, 'Trololoooo-oo-o-ooo', 'CZK'],
		];
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
	 * @param string $rawAmount
	 * @param string $rawCurrency
	 * @return MoneyInput
	 */
	private function getInputWithMockedUserInput($rawAmount, $rawCurrency)
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



(new MoneyInputValidatorsTest())->run();
