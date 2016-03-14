<?php

namespace Achse\MoneyInput;

use Kdyby\Money\Currency;
use Kdyby\Money\Money;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\InvalidArgumentException;



class MoneyInput extends TextInput
{

	/**
	 * Protection from value-overflow. Do not forget to COUNT SPACES. Because: '1 000 000 000' -> 13, not 10.
	 */
	const AMOUNT_LENGTH_LIMIT = 13;

	const CLASS_IDENTIFIER = 'money-input';

	/**
	 * @var Currency|NULL
	 */
	private $currency;



	/**
	 * @param Currency $currency
	 * @param string|NULL $label
	 * @param int $maxLength
	 */
	public function __construct(Currency $currency = NULL, $label = NULL, $maxLength = self::AMOUNT_LENGTH_LIMIT)
	{
		parent::__construct($label, $maxLength);

		$this->currency = $currency;

		$this->addCondition(Form::FILLED)
			->addRule(Form::PATTERN, 'moneyInput.error.notANumber', '[0-9 ]+')
			->addRule(Form::MAX_LENGTH, 'moneyInput.error.numberTooBig', self::AMOUNT_LENGTH_LIMIT);

		$this->setAttribute('class', self::CLASS_IDENTIFIER);
	}



	/**
	 * @param \DateTime|string $value
	 * @return static
	 */
	public function setValue($value)
	{
		if ($value instanceof Money) {
			$value = $value->toFloat();
		}

		$this->rawValue = $value;

		return parent::setValue($value);
	}



	/**
	 * @return Money|NULL
	 */
	public function getValue()
	{
		if (($value = parent::getValue()) === '') {
			return NULL;
		}

		return Money::fromFloat($value, $this->currency);
	}



	/**
	 * @inheritdoc
	 */
	public function setDefaultValue($value)
	{
		if (!$value instanceof Money) {
			$type = gettype($value);

			throw new InvalidArgumentException(
				"As default value, Money object must be given. '"
				. ($type === 'object' ? get_class($value) : $type) . "' given instead"
			);
		}

		return parent::setDefaultValue($value);
	}

}
