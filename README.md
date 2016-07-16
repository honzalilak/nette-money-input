[![Downloads this Month](https://img.shields.io/packagist/dm/achse/nette-money-input.svg)](https://packagist.org/packages/achse/nette-money-input)
[![Latest Stable Version](https://poser.pugx.org/achse/nette-money-input/v/stable)](https://github.com/achse/nette-money-input/releases)
![](https://travis-ci.org/Achse/nette-money-input.svg?branch=master)
![](https://scrutinizer-ci.com/g/Achse/nette-money-input/badges/quality-score.png?b=master)
![](https://scrutinizer-ci.com/g/Achse/nette-money-input/badges/coverage.png?b=master)

![](https://raw.githubusercontent.com/Achse/nette-money-input/master/examples/preview.png)

# Disclaimer
As support for multi-control inputs in Nette is not really rich, this component have some limitations:
* There is hardcoded rendering for Bootstrap3, you are not force to use it, but the structure of elements and classes attached to it are hardcoded. If you want to use your own "skin" you can use:
 * Methods `getAmountControlPrototype()` and `getCurrencyControlPrototype()` to reach input and select elements.
 * CSS classes `moneyInputControlContainer`, `moneyInputAmountContainer` and `moneyInputCurrencyContainer` to adjust placement of inputs.
* There is also limitation in `addRule()` there is support only for:
 * `Form::FILLED` and `Form::REQUIRED` - You can also use `setRequired()`.
 * and `Form::RANGE` - But there is no support for currencies. Its recommended to use it for positive / negative limitation only.

# Installation

## Composer:
```bash
composer require achse/nette-money-input
```

## Javascript Dependencies
```
npm install jquery
```

If you are using webloader:
```yaml
webloader:
	js:
		default:
			files:
				- %wwwDir%/../vendor/achse/nette-money-input/assets/moneyInput.js
```

# Usage

## Without currency information

You need to implement your `ICurrencyFinder` to transform Currency-Code string to object.
```php
class CurrencyFinder extends Object implements ICurrencyFinder
{
	private $currencyRepository;

	public function __construct(EntityManager $entityManager)
	{
		$this->currencyRepository = $entityManager->getRepository(Currency::class);
	}

	/**
	 * @inheritdoc
	 */
	public function findByCode($code)
	{
		/** @var Currency $currency */
		$currency = $this->currencyRepository->findOneBy(['code' => $code]);
		if ($currency === NULL) {
			throw new CurrencyNotFoundException("Currency '{$code}' not found.");
		}

		return $currency;
	}
}
```

And then define `addMoney` method in you whatever `Form` class / trait.
```php
/**
 * @param string $name
 * @param string|NULL $label
 * @param array $currencyCodeOptions
 * @param ICurrencyFinder $currencyFinder
 * @return MoneyInput
 */
public function addMoney($name, $label = NULL, array $currencyCodeOptions, ICurrencyFinder $currencyFinder)
{
	$input = new MoneyInput($label, MoneyInput::AMOUNT_LENGTH_LIMIT, $currencyCodeOptions, $currencyFinder);

	return $this[$name] = $input;
}
```
