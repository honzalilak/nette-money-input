<?php

namespace Achse\MoneyInput;

use Kdyby\Money\Money;
use Nette\Forms\IControl;
use Nette\InvalidArgumentException;
use Nette\Object;



class MoneyInputValidators extends Object
{

	/**
	 * @param IControl $control
	 * @return bool
	 */
	public static function validateMoneyInputValid(IControl $control)
	{
		/** @var MoneyInput $control */
		self::validateControlType($control);

		return $control->isEmpty() || $control->isValid();
	}



	/**
	 * @param IControl $control
	 * @return bool
	 */
	public static function validateMoneyInputFilled(IControl $control)
	{
		/** @var MoneyInput $control */
		self::validateControlType($control);

		return !$control->isEmpty();
	}



	/**
	 * @param IControl $control
	 * @param float[]|NULL[] $args
	 * @return bool
	 */
	public static function validateMoneyInputRange(IControl $control, array $args)
	{
		/** @var MoneyInput $control */
		self::validateControlType($control);
		self::validateRangeArgs($args);

		$value = self::getMoneyValue($control);

		$left = self::createMoneyLimitFromInput($value, $args[0]);
		$right = self::createMoneyLimitFromInput($value, $args[1]);

		return (
			($left === NULL || $left->lessOrEquals($value))
			&& ($right === NULL || $right->largerOrEquals($value))
		);
	}



	/**
	 * @param IControl $control
	 */
	private static function validateControlType(IControl $control)
	{
		if (!$control instanceof MoneyInput) {
			throw new InvalidArgumentException(
				"Given control object must be instance of '" . get_class()
				. "', but '" . get_class($control) . "' given."
			);
		}
	}



	/**
	 * @param MoneyInput $control
	 * @return Money
	 */
	private static function getMoneyValue(MoneyInput $control)
	{
		$value = $control->getValue();
		if ($value === NULL) {
			$value = Money::from(0);
		}

		return $value;
	}



	/**
	 * @param Money $value
	 * @param float|NULL $input
	 * @return Money|NULL
	 */
	private static function createMoneyLimitFromInput(Money $value, $input)
	{
		return $input !== NULL ? Money::fromFloat($input, $value->getCurrency()) : NULL;
	}



	/**
	 * @param float[]|NULL[] $args
	 */
	private static function validateRangeArgs(array $args)
	{
		if (count($args) !== 2) {
			throw new InvalidArgumentException('You have to provide exactly two values.');
		}
	}
	
}
