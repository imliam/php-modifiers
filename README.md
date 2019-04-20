# PHP Modifiers

[![Latest Version on Packagist](https://img.shields.io/packagist/v/imliam/php-modifiers.svg)](https://packagist.org/packages/imliam/php-modifiers)
[![Build Status](https://img.shields.io/travis/imliam/php-modifiers.svg)](https://travis-ci.org/imliam/php-modifiers)
![Code Quality](https://img.shields.io/scrutinizer/g/imliam/php-modifiers.svg)
![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/imliam/php-modifiers.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/imliam/php-modifiers.svg)](https://packagist.org/packages/imliam/php-modifiers)
[![License](https://img.shields.io/github/license/imliam/php-modifiers.svg)](LICENSE.md)

Adds the ability to apply a range of optional modifiers `! @ ~ + -` when calling your class methods to augment them with a unique syntax.

For example, we may have a class where each of the following may do different things:

```php
Slack::say('Hello world'); // 'Hello world'
!Slack::say('Hello world'); // 'HELLO WORLD'
@Slack::say('Hello world'); // 'Someone said "Hello world"'
@!Slack::say('Hello world'); // 'Someone said "HELLO WORLD"'
```

<!-- TOC -->

- [PHP Modifiers](#php-modifiers)
    - [💾 Installation](#💾-installation)
    - [📝 Usage](#📝-usage)
        - [Global Functions](#global-functions)
    - [The Modifiers](#the-modifiers)
        - [`!` Exclamation Mark](#-exclamation-mark)
        - [`+` Plus Symbol](#-plus-symbol)
        - [`-` Minus Symbol](#--minus-symbol)
        - [`~` Tilde Symbol](#-tilde-symbol)
        - [`@` "At" Symbol](#-at-symbol)
    - [How It Works](#how-it-works)
    - [✅ Testing](#✅-testing)
    - [🔖 Changelog](#🔖-changelog)
    - [⬆️ Upgrading](#⬆️-upgrading)
    - [🎉 Contributing](#🎉-contributing)
        - [🔒 Security](#🔒-security)
    - [👷 Credits](#👷-credits)
    - [♻️ License](#♻️-license)

<!-- /TOC -->

## 💾 Installation

You can install the package with [Composer](https://getcomposer.org/) using the following command:

```bash
composer require imliam/php-modifiers:^1.0.0
```

## 📝 Usage

This package's functionality is exposed through the `HasModifiers` trait, which can be applied to a class:

```php
class Example
{
    use HasModifiers;
}
```

With the trait applied to the class, you must also define the class methods that can have modifiers used. This can be done by setting a static `$modifierAliases` property on the class.

This property should be an array that contains pairs of the class and method name that can be used.

For example if we wanted the `say` method on the current class to be able to use modifiers:

```php
public static $modifierAliases = [
    [self::class, 'say'],
];
```

With this set up, inside the method we can now check if certain modifiers have been used by calling the `static::hasModifier()` method:

```php
public static function say($string)
{
    if (static::hasModifier('!')) {
        echo strtoupper($string);
        return;
    }

    echo $string;
}
```

With it all in place, we can now call our method either with or without the modifier to get the desired behaviour:

```php
Example::say('Hello world'); // 'Hello world'
!Example::say('Hello world'); // 'HELLO WORLD'
```

### Global Functions

If we wanted to register a global function that can accept modifiers, we can use one to call our class method, as well as registering it as an alias by adding it to the `$modifierAliases` property.

```php
function say($string)
{
    return Example::say($string);
}

Example::$modifierAliases[] = 'say';
```

The function will now work in the same way as before:

```php
say('Hello world'); // 'Hello world'
!say('Hello world'); // 'HELLO WORLD'
```

## The Modifiers

There are five modifiers available to use. It is very important to note that as they are all also regular operators in PHP, each of them have their own quirks that might change the expected behaviour and return values of the methods.

Using them

Due to this, it would make sense to only use these modifiers on void functions that are not expected to return anything of use.

### `!` Exclamation Mark

**Do not use the return value with this modifier.**

The exclamation mark is a [logical operator](https://www.php.net/manual/en/language.operators.logical.php) in PHP that negates the value to the opposite boolean. This means that any truthy value that is returned will be false, and any falsy value returned will be true.

### `+` Plus Symbol

**Do not use the return value with this modifier.**

The plus symbol is an identity [arithmetic operator](https://www.php.net/manual/en/language.operators.arithmetic.php) in PHP. Used as a modifier, it will attempt to cast the return value to an integer or float.

### `-` Minus Symbol

**Do not use the return value with this modifier.**

The plus symbol is a negative identity [arithmetic operator](https://www.php.net/manual/en/language.operators.arithmetic.php) in PHP. Used as a modifier, it will attempt to cast the return value to an integer or float.

### `~` Tilde Symbol

**Do not use the return value with this modifier.**

The tilde symbol is a [bitwise operator](https://www.php.net/manual/en/language.operators.bitwise.php) in PHP. However, as the operator itself performs a bitwise operation, any method using this operator **must return an integer value**, or something that can be cast to one.

### `@` "At" Symbol

**You can use the return value with this modifier.**

The "at" symbol is an [error suppression operator](https://www.php.net/manual/en/language.operators.errorcontrol.php) in PHP that hides and ignores any errors that occur in the proceeding statement - meaning errors that might be within your method will be unexpectedly ignored.

However, this is the only one of the operators that does not alter the return value of the method.

```php
public static function hasSomething()
{
    if (static::hasModifier('@')) {
        trigger_error("An error should occur here, but it gets ignored…");

        return 'A modified value…';
    }

    return 'A non-modified value…';
}

echo Example::hasSomething(); // 'A non-modified value…'
echo @Example::hasSomething(); // 'A modified value…'
```

## How It Works

✨ Magic ✨

The modifier functionality works by taking a stack trace at the point the method was called, finding the point aliased method was called further up in the stack trace. Once these aliases are found, the file is read as a string and the [PHP source tokens are parsed](https://php.net/manual/en/function.token-get-all.php) to find the operators that came before it.

This was originally seen in the [Kint](https://github.com/kint-php/kint) package as a way to quickly augment how your variables are displayed while debugging. Because it was designed for this environment, great performance was not a huge concern. Following stack traces and parsing source code are generally _slow_ operations in PHP, so take it with a grain of salt and don't abuse it in large production applications.

Check the source code to see how it works in more depth. This package is a stripped down version of Kint's implementation that fits in a couple of small source files.

## ✅ Testing

``` bash
composer test
```

## 🔖 Changelog

Please see [the changelog file](CHANGELOG.md) for more information on what has changed recently.

## ⬆️ Upgrading

Please see the [upgrading file](UPGRADING.md) for details on upgrading from previous versions.

## 🎉 Contributing

Please see the [contributing file](CONTRIBUTING.md) and [code of conduct](CODE_OF_CONDUCT.md) for details on contributing to the project.

### 🔒 Security

If you discover any security related issues, please email liam@liamhammett.com instead of using the issue tracker.

## 👷 Credits

- [Liam Hammett](https://github.com/imliam)
- [Kint](https://github.com/kint-php/kint) and all of its contributors for the implementation
- [All Contributors](../../contributors)

## ♻️ License

The MIT License (MIT). Please see the [license file](LICENSE.md) for more information.
