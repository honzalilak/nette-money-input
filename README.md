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
```php

// In your `BaseForm` class / `FormElemetns` trait, or whatever ...

/**
 * @param string $name
 * @param string|NULL $label
 * @return MoneyInput
 */
public function addMoney($name, $label = NULL)
{
	return $this[$name] = new MoneyInput(NULL, $label);
}


// Processing

$currency = $this->currencyFinder->findByCode($values->currency);
$amount = Money::from($values->amount->toInt(), $currency);
