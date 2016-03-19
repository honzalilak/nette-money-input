[![Downloads this Month](https://img.shields.io/packagist/dm/achse/nette-money-input.svg)](https://packagist.org/packages/achse/nette-money-input)
[![Latest Stable Version](https://poser.pugx.org/achse/nette-money-input/v/stable)](https://github.com/achse/nette-money-input/releases)
![](https://travis-ci.org/Achse/nette-money-input.svg?branch=master)
![](https://scrutinizer-ci.com/g/Achse/nette-money-input/badges/quality-score.png?b=master)
![](https://scrutinizer-ci.com/g/Achse/nette-money-input/badges/coverage.png?b=master)

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
```neon
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
