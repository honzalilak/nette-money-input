<?php

namespace Achse\MoneyInput;

use Kdyby\Money\Money;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Forms\Helpers;
use Nette\Forms\IControl;
use Nette\InvalidArgumentException;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Nette\Utils\Validators;



class MoneyInput extends TextInput
{

	/**
	 * Protection from value-overflow. Do not forget to COUNT SPACES. Because: '1 000 000 000' -> 13, not 10.
	 */
	const AMOUNT_LENGTH_LIMIT = 13;

	const CLASS_IDENTIFIER = 'money-input';

	/**
	 * @var string
	 */
	private $rawAmount = '';

	/**
	 * @var string
	 */
	private $rawCurrencyCode = '';

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

		$this->addRule(Form::VALID, 'moneyInput.error.notANumber');
	}



	public function loadHttpData()
	{
		$rawAmount = $this->getHttpData(Form::DATA_LINE, '[amount]');
		$this->rawAmount = Strings::replace($rawAmount, '~\s~', '');
		$rawCurrencyCode = $this->getHttpData(Form::DATA_LINE, '[currencyCode]');
		$this->rawCurrencyCode = Strings::replace($rawCurrencyCode, '~\s~', '');
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
				->value($this->rawAmount)
				->class(self::CLASS_IDENTIFIER)
			)
			->add(Helpers::createSelectBox($this->currencyCodeOptions, ['selected?' => $this->rawCurrencyCode])
				->name($name . '[currencyCode]'));
	}



	/**
	 * @inheritdoc
	 */
	public function isFilled()
	{
		return !$this->isEmpty();
	}



	/**
	 * @param Money|string $value
	 * @return static
	 */
	public function setValue($value)
	{
		if ($value instanceof Money) {
			$this->rawCurrencyCode = $value->getCurrency()->getCode();
			$this->rawAmount = (string) $value->toFloat();
		} else {
			$this->rawAmount = '';
			$this->rawCurrencyCode = '';
		}
	}



	/**
	 * @return Money|NULL
	 * @throws CurrencyNotFoundException
	 */
	public function getValue()
	{
		/** @var float|NULL $amount */
		/** @var string|NULL $currencyCode */
		list ($amount, $currencyCode) = $this->parseRawData();

		if ($amount === NULL || $currencyCode === NULL) {
			return NULL;
		}

		return Money::fromFloat($amount, $this->currencyFinder->findByCode($currencyCode));
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



	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		try {
			/** @var float|NULL $amount */
			/** @var string|NULL $currencyCode */
			list ($amount, $currencyCode) = $this->parseRawData();

		} catch (ValueParseException $e) {
			// If parsing failed, there is definitely something inside

			return FALSE;
		}

		return (
			$amount === NULL
			|| $currencyCode === NULL
			|| $this->rawAmount == 0 // intentionally loose comparison (int vs. float: 0 !== 0.0)
		);
	}



	/**
	 * @return bool
	 */
	public function isValid()
	{
		return Validators::isNumeric($this->rawAmount);
	}



	/**
	 * @inheritdoc
	 */
	public function setRequired($value = TRUE)
	{
		if ($value) {
			$this->addRule(Form::REQUIRED, is_string($value) ? $value : NULL);
		} else {
			$this->getRules()->setRequired(FALSE);
		}

		return $this;
	}



	/**
	 * @inheritdoc
	 */
	public function addRule($operation, $message = NULL, $arg = NULL)
	{
		if ($operation === Form::FILLED || $operation === Form::REQUIRED) {
			$operation = __CLASS__ . '::validateMoneyInputFilled';

		} elseif ($operation === Form::VALID) {
			$operation = __CLASS__ . '::validateMoneyInputValid';
		}

		return parent::addRule($operation, $message, $arg);
	}



	/**
	 * @param IControl $control
	 * @return bool
	 */
	public static function validateMoneyInputValid(IControl $control)
	{
		/** @var static $control */
		self::validateControlType($control);

		return $control->isEmpty() || $control->isValid();
	}



	/**
	 * @param IControl $control
	 * @return bool
	 */
	public static function validateMoneyInputFilled(IControl $control)
	{
		/** @var static $control */
		self::validateControlType($control);

		return !$control->isEmpty();
	}



	/**
	 * @param IControl $control
	 */
	private static function validateControlType(IControl $control)
	{
		if (!$control instanceof static) {
			throw new InvalidArgumentException(
				"Given control object must be instance of '" . get_class()
				. "', but '" . get_class($control) . "' given."
			);
		}
	}



	/**
	 * @return float[]|NULL[]|string[]
	 * @throws ValueParseException
	 */
	private function parseRawData()
	{
		if ($this->rawAmount !== '') {
			if (!Validators::isNumeric($this->rawAmount)) {
				throw new ValueParseException();
			}

			$amount = (float) $this->rawAmount;
		} else {
			$amount = NULL;
		}

		$currencyCode = $this->rawCurrencyCode !== '' ? $this->rawCurrencyCode : NULL;

		return [$amount, $currencyCode];
	}

}
