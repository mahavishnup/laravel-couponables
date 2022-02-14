![Laravel Couponables](https://user-images.githubusercontent.com/37669560/153603606-25f56bec-879c-4ec0-a061-fb11907e5e4e.png)

# Laravel Couponables
[![Latest Version on Packagist](https://img.shields.io/packagist/v/michael-rubel/laravel-couponables.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/michael-rubel/laravel-couponables)
[![Total Downloads](https://img.shields.io/packagist/dt/michael-rubel/laravel-couponables.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/michael-rubel/laravel-couponables)
[![Code Quality](https://img.shields.io/scrutinizer/quality/g/michael-rubel/laravel-couponables.svg?style=flat-square&logo=scrutinizer)](https://scrutinizer-ci.com/g/michael-rubel/laravel-couponables/?branch=main)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/michael-rubel/laravel-couponables.svg?style=flat-square&logo=scrutinizer)](https://scrutinizer-ci.com/g/michael-rubel/laravel-couponables/?branch=main)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/michael-rubel/laravel-couponables/run-tests/main?style=flat-square&label=tests&logo=github)](https://github.com/michael-rubel/laravel-couponables/actions)
[![PHPStan](https://img.shields.io/github/workflow/status/michael-rubel/laravel-couponables/phpstan/main?style=flat-square&label=larastan&logo=laravel)](https://github.com/michael-rubel/laravel-couponables/actions)

This package provides polymorphic coupon functionality for your Laravel application.

The package requires PHP `^8.x` and Laravel `^8.71`.
Laravel 9 is supported as well.

[![PHP Version](https://img.shields.io/badge/php-^8.x-777BB4?style=flat-square&logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-^8.71-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Laravel Octane Compatible](https://img.shields.io/badge/octane-compatible-success?style=flat-square&logo=laravel)](https://github.com/laravel/octane)

## Installation
Install the package using composer:
```bash
composer require michael-rubel/laravel-couponables
```

Publish the migrations:
```bash
php artisan vendor:publish --tag="couponables-migrations"
```

Publish the config file:
```bash
php artisan vendor:publish --tag="couponables-config"
```

## Usage
After publishing migrations you can use trait in any of your models:
```php
use HasCoupons;
```

Seed your database with coupon codes using `Coupon` model, then apply the code using:
```php
$model->redeemCoupon($code);
```

`redeemCoupon` method throws an exception if something's wrong:

```php
CouponExpiredException      // Coupon is expired (`expires_at` column).
InvalidCouponException      // Coupon is not found in the database.
NotAllowedToRedeemException // Coupon is assigned to the specific model (`redeemer` morphs).
OverLimitException          // Coupon is over the limit for the specific model (`limit` column).
OverQuantityException       // Coupon is exhausted (`quantity` column).
```

Check if this coupon is already redeemed by the model (at least one record exists in the `couponables` table):
```php
$model->isCouponAlreadyUsed($code);
```

### Available [Coupon](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Models/Coupon.php) model API:
```php
public function isExpired(): bool;
public function isNotExpired(): bool;
public function isOverQuantity(): bool;
public function isOverLimitFor(Model $redeemer): bool;
```

This method references the model assigned to redeem the coupon:
```php
public function redeemer(): ?Model;
```

### Listeners
If you go event-driven, you can handle package events:
- [CouponRedeemed](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Events/CouponRedeemed.php)

### Overriding the package functionality
Traits [DefinesColumns](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Models/Traits/DefinesColumns.php) and [DefinesPivotColumns](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Models/Traits/DefinesPivotColumns.php) contain the methods that define column names to use by the package. You can use a method binding to override the package's method behavior.

Example method binding in your ServiceProvider:
```php
bind(CouponContract::class)->method('getCodeColumn', fn () => 'coupon')
// This method returns the `coupon` column name instead of `code` from now.
```

Alternatively, you can extend/override the entire class using [config values](https://github.com/michael-rubel/laravel-couponables/blob/main/config/couponables.php) or [container bindings](https://github.com/michael-rubel/laravel-couponables/blob/main/src/CouponableServiceProvider.php). All the classes in the package have their own contract (interface), so you're free to modify it as you wish.

## Contributing
If you see any ways we can improve the package, PRs are welcomed. But remember to write tests for your use cases.

## Testing
```bash
composer test
```
