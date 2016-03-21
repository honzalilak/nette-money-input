<?php

namespace Achse\MoneyInput;

use Kdyby\Money\Money;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Forms\Helpers;
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

	const RULES_MAP = [
		Form::FILLED => MoneyInputValidators::class . '::validateMoneyInputFilled',
		Form::REQUIRED => MoneyInputValidators::class . '::validateMoneyInputFilled',
		Form::VALID => MoneyInputValidators::class . '::validateMoneyInputValid',
		Form::RANGE => MoneyInputValidators::class . '::validateMoneyInputRange',
	];

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
	 * @var Html
	 */
	private $amountControl;

	/**
	 * @var Html
	 */
	private $currencyControl;



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

		$this->amountControl = Html::el('input');
		$this->currencyControl = Helpers::createSelectBox($currencyCodeOptions, ['selected?' => $this->rawCurrencyCode]);

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
	 * @return Html
	 */
	public function getControl()
	{
		$name = $this->getHtmlName();

		$amountControl = clone $this->amountControl;
		$currencyControl = clone $this->currencyControl;

		$amountControl
			->name($name . '[amount]')
			->id($this->getHtmlId())
			->value($this->rawAmount)
			->class(self::CLASS_IDENTIFIER . ' form-control');

		$currencyControl
			->name($name . '[currencyCode]')
			->class('form-control');

		return Html::el('div')
			->add(
				Html::el('div')->add($amountControl)->class('col-sm-9 moneyInputAmountContainer')
			)
			->add(
				Html::el('div')->add($currencyControl)->class('col-sm-3 moneyInputCurrencyContainer')
			)
			->class('row moneyInputControlContainer');
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

		return $this;
	}



	/**
	 * @return Money|NULL
	 */
	public function getValue()
	{
		try {
			/** @var float|NULL $amount */
			/** @var string|NULL $currencyCode */
			list ($amount, $currencyCode) = $this->parseRawData();

			if ($amount === NULL || $currencyCode === NULL) {
				return NULL;
			}
		} catch (ValueParseException $e) {
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
		if (array_key_exists($operation, self::RULES_MAP)) {
			$operation = self::RULES_MAP[$operation];
		}

		return parent::addRule($operation, $message, $arg);
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
			$amount = 0;
		}

		$currencyCode = $this->rawCurrencyCode !== '' ? $this->rawCurrencyCode : NULL;

		return [$amount, $currencyCode];
	}



	/**
	 * @return Html
	 */
	public function getAmountControlPrototype()
	{
		return $this->amountControl;
	}



	/**
	 * @return Html
	 */
	public function getCurrencyControlPrototype()
	{
		return $this->currencyControl;
	}

}
