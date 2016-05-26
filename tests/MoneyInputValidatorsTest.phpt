<?php

/**
 * @testCase
 */

namespace Achse\MoneyInput\Tests;

require __DIR__ . '/bootstrap.php';

use Achse\MoneyInput\MoneyInputValidators;
use Mockery;
use Mockery\MockInterface;
use Nette\Forms\IControl;
use Nette\InvalidArgumentException;
use Tester\Assert;
use Tester\TestCase;



class MoneyInputValidatorsTest extends TestCase
{

	/**
	 * @var MoneyInputMocker
	 */
	private $mocker;



	public function __construct()
	{
		$this->mocker = new MoneyInputMocker();
	}



	/**
	 * @dataProvider getDataForValidateMoneyInputFilled
	 *
	 * @param bool $expected
	 * @param string $rawAmount
	 * @param string $rawCurrency
	 */
	public function testValidateMoneyInputValid($expected, $rawAmount, $rawCurrency)
	{
		$input = $this->mocker->getInputWithMockedUserInput($rawAmount, $rawCurrency);
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



	public function testTypeValidation()
	{
		/** @var IControl|MockInterface $control */
		$control = Mockery::mock(IControl::class);

		Assert::exception(function () use ($control) {
			MoneyInputValidators::validateMoneyInputFilled($control);
		}, InvalidArgumentException::class);

		Assert::exception(function () use ($control) {
			MoneyInputValidators::validateMoneyInputValid($control);
		}, InvalidArgumentException::class);

		Assert::exception(function () use ($control) {
			MoneyInputValidators::validateMoneyInputRange($control, []);
		}, InvalidArgumentException::class);
	}

}



(new MoneyInputValidatorsTest())->run();
