<?php

namespace Achse\MoneyInput;

use Kdyby\Money\Money;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Forms\Helpers;
use Nette\InvalidArgumentException;
use Nette\Utils\Html;
use Nette\Utils\Strings;



class MoneyInput extends TextInput
{

	/**
	 * Protection from value-overflow. Do not forget to COUNT SPACES. Because: '1 000 000 000' -> 13, not 10.
	 */
	const AMOUNT_LENGTH_LIMIT = 13;

	const CLASS_IDENTIFIER = 'money-input';

	/**
	 * @var float|NULL
	 */
	private $amount;

	/**
	 * @var string|NULL
	 */
	private $currencyCode;

	/**
	 * @var ICurrencyFinder
	 */
	private $currencyFinder;

	/**
	 * @var string[]
	 */
	private $currencyCodeOptions;



	/**
	 * @param string|NULL $label
	 * @param int $maxLength
	 * @param array $currencyCodeOptions
	 * @param ICurrencyFinder $currencyFinder
	 */
	public function __construct(
		$label = NULL,
		$maxLength = self::AMOUNT_LENGTH_LIMIT,
		array $currencyCodeOptions,
		ICurrencyFinder $currencyFinder
	) {
		parent::__construct($label, $maxLength);

		$this->currencyFinder = $currencyFinder;
		$this->currencyCodeOptions = $currencyCodeOptions;

		$this->addCondition(Form::FILLED)
			->addRule(Form::PATTERN, 'moneyInput.error.notANumber', '[0-9 ]+')
			->addRule(Form::MAX_LENGTH, 'moneyInput.error.numberTooBig', self::AMOUNT_LENGTH_LIMIT);
	}



	public function loadHttpData()
	{
		$rawAmount = $this->getHttpData(Form::DATA_LINE, '[amount]');
		$this->amount = (float) Strings::replace($rawAmount, '~\s~', '');
		$this->currencyCode = $this->getHttpData(Form::DATA_LINE, '[currencyCode]');
	}



	/**
	 * @inheritdoc
	 */
	public function getControl()
	{
		$name = $this->getHtmlName();

		return Html::el()
			->add(Html::el('input')
				->name($name . '[amount]')
				->id($this->getHtmlId())
				->value($this->amount)
				->class(self::CLASS_IDENTIFIER)
			)
			->add(Helpers::createSelectBox($this->currencyCodeOptions, ['selected?' => $this->currencyCode])
				->name($name . '[currencyCode]'));
	}



	/**
	 * @inheritdoc
	 */
	public function isFilled()
	{
		return (
			$this->amount !== NULL
			&& $this->amount !== 0
			&& $this->currencyCode !== NULL
			&& $this->currencyCode !== ''
		);
	}



	/**
	 * @param Money|string $value
	 * @return static
	 */
	public function setValue($value)
	{
		if ($value instanceof Money) {
			$this->currencyCode = $value->getCurrency()->getCode();
			$this->amount = $value->toFloat();
		} else {
			$this->amount = NULL;
			$this->currencyCode = NULL;
		}
	}



	/**
	 * @return Money|NULL
	 * @throws CurrencyNotFoundException
	 */
	public function getValue()
	{
		if ($this->amount === NULL || $this->currencyCode === NULL) {
			return NULL;
		}

		return Money::fromFloat($this->amount, $this->currencyFinder->findByCode($this->currencyCode));
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
