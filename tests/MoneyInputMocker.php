<?php

namespace Achse\MoneyInput\Tests;

use Achse\MoneyInput\ICurrencyFinder;
use Achse\MoneyInput\MoneyInput;
use Kdyby\Money\Currency;
use Mockery;
use Mockery\MockInterface;
use Nette\Forms\Form;
use ReflectionObject;



class MoneyInputMocker
{

	const DUMMY_CURRENCY_OPTIONS = ['CZK' => 'CZK'];

	/**
	 * @var string[]
	 */
	private $currencyOptions;



	public function __construct($currencyOptions = self::DUMMY_CURRENCY_OPTIONS)
	{
		$this->currencyOptions = $currencyOptions;
	}



	/**
	 * @return ICurrencyFinder|MockInterface
	 */
	public function mockCurrencyFinder()
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
			$this->currencyOptions,
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
