---
slug: throwing-and-creating-custom-exceptions
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: error-handling
chapterTitle: "Chapter 4 — Error Handling"
chapterOrder: 4
title: "Throwing and Creating Custom Exceptions"
difficulty: intermediate
order: 3
estimatedMinutes: 10
tags: [exceptions, custom-exceptions, throw, oop]
---

## Learning Objective

By the end of this lesson, you will be able to use the `throw` keyword to raise your own exceptions, create a custom exception class by extending PHP's built-in `Exception` class, and explain why custom exception types make error handling clearer in real applications.

## Explanation

So far you have seen PHP and its built-in functions throw exceptions on their own — for example, dividing by zero, or calling `is_numeric()` failing a check that you then reported with `\InvalidArgumentException`. But you are not limited to PHP's built-in exception types. You can raise your own exceptions at any point in your code using the `throw` keyword, and you can design entirely new exception classes tailored to your application's needs.

The simplest way to raise an exception is:

```
throw new \Exception("Something specific went wrong here");
```

`throw` immediately stops normal execution at that point and hands the exception object up to the nearest matching `catch` block (or, if there is none, up and out of the script, producing an uncaught error).

While you could throw a plain `\Exception` everywhere, real projects usually define **custom exception classes** — classes that extend PHP's built-in `Exception` class (or one of its more specific relatives, like `\RuntimeException` or `\InvalidArgumentException`). A custom exception class looks like this:

```
class InsufficientFundsException extends \Exception
{
}
```

That is a complete, valid exception class — by extending `\Exception`, it automatically inherits everything it needs: a constructor that accepts a message and error code, `getMessage()`, `getCode()`, `getTraceAsString()`, and more. You can also add your own extra data to a custom exception, such as storing exactly how much money was missing:

```
class InsufficientFundsException extends \Exception
{
    public function __construct(
        string $message,
        private float $shortfall
    ) {
        parent::__construct($message);
    }

    public function getShortfall(): float
    {
        return $this->shortfall;
    }
}
```

Here, `parent::__construct($message)` calls the constructor of the built-in `\Exception` class to properly set up the message, while the custom `getShortfall()` method gives calling code extra, specific information about the problem — something a plain `\Exception` could never provide.

## Why It Matters

In a small script, throwing a generic `\Exception` everywhere might seem fine. But in a real application with many different things that can go wrong — a missing user, insufficient funds in a wallet, an expired coupon code, invalid file uploads — catching everything as one generic `\Exception` type makes it impossible for calling code to react differently to different problems. If every problem looks the same to the `catch` block, you cannot show "Your session expired, please log in again" versus "This coupon code is no longer valid" versus "You don't have permission to do that" — you would have to inspect the message text, which is fragile and error-prone.

Custom exception classes solve this. A function that looks up a user can throw a `UserNotFoundException`, and calling code can catch that exact type and know precisely what happened — no guessing based on string matching. This is exactly the pattern used throughout professional PHP frameworks like Symfony and Laravel, where you will constantly see and write exception classes named after the specific problem they represent, such as `NotFoundHttpException` or `AuthenticationException`.

## Example

Imagine an online store that needs to validate a discount coupon before applying it to an order. If the coupon does not exist, or has expired, those are two different problems that calling code might want to react to differently — so we will model each as its own custom exception class.

## Code

```php
<?php

class CouponNotFoundException extends \Exception
{
}

class CouponExpiredException extends \Exception
{
    public function __construct(
        string $message,
        private readonly \DateTimeImmutable $expiredOn
    ) {
        parent::__construct($message);
    }

    public function getExpiredOn(): \DateTimeImmutable
    {
        return $this->expiredOn;
    }
}

function validateCoupon(array $coupons, string $code): void
{
    if (!array_key_exists($code, $coupons)) {
        throw new CouponNotFoundException("Coupon '{$code}' does not exist");
    }

    $expiresOn = $coupons[$code];
    $today = new \DateTimeImmutable('2026-09-18');

    if ($today > $expiresOn) {
        throw new CouponExpiredException(
            "Coupon '{$code}' has expired",
            $expiresOn
        );
    }
}

$coupons = [
    'SAVE10' => new \DateTimeImmutable('2026-12-31'),
    'OLDDEAL' => new \DateTimeImmutable('2025-01-01'),
];

foreach (['SAVE10', 'OLDDEAL', 'UNKNOWN'] as $code) {
    try {
        validateCoupon($coupons, $code);
        echo "Coupon '{$code}' is valid!" . PHP_EOL;
    } catch (CouponNotFoundException $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    } catch (CouponExpiredException $e) {
        echo "Error: " . $e->getMessage() .
            " (expired on " . $e->getExpiredOn()->format('Y-m-d') . ")" . PHP_EOL;
    }
}
```

## Code Explanation

- `class CouponNotFoundException extends \Exception {}` defines a custom exception with no extra behavior — it exists purely so calling code can catch this specific, meaningful type instead of a generic one.
- `class CouponExpiredException extends \Exception` adds a custom constructor that stores an extra piece of data, `$expiredOn`, alongside the normal message. `parent::__construct($message)` makes sure the base `\Exception` machinery (like `getMessage()`) still works correctly.
- `getExpiredOn()` is a custom method — not part of the built-in `\Exception` class — that exposes the extra data so catching code can use it, such as displaying the actual expiration date to the user.
- `validateCoupon()` throws one of the two custom exceptions depending on exactly what is wrong: a missing coupon versus an expired one.
- The `foreach` loop tries three coupon codes. `SAVE10` is valid and prints a success message. `OLDDEAL` is expired, so the `CouponExpiredException` branch runs and prints the expiration date using the custom `getExpiredOn()` method. `UNKNOWN` does not exist at all, so `CouponNotFoundException` is caught instead.

## Common Mistakes

- **Throwing plain `\Exception` for everything.** This forces every caller to either catch too broadly or inspect the message text to figure out what actually happened, which is fragile if the wording ever changes.
- **Forgetting to call `parent::__construct()`** when overriding the constructor of a custom exception. If you skip it, `getMessage()` may return an empty string, making the exception far less useful.
- **Creating a huge hierarchy of near-identical exception classes** for trivial distinctions. Custom exceptions are valuable when calling code genuinely needs to react differently — don't create one for every tiny variation if a single type with a detailed message would do.
- **Catching your own custom exception and then re-throwing a completely unrelated generic exception**, which discards the specific, useful information you just created the class to carry.

## Best Practices

- Name custom exception classes after the specific problem they represent, ending in `Exception` (e.g. `UserNotFoundException`, `InsufficientFundsException`) so their purpose is obvious at a glance.
- Extend the most fitting built-in exception when it makes sense (`\InvalidArgumentException` for bad input, `\RuntimeException` for something that went wrong during execution) rather than always extending the base `\Exception`, so catching code can also catch by the broader built-in category if it wants to.
- Add extra properties and getter methods to custom exceptions when the calling code genuinely benefits from more structured information than a plain text message.

## Exercise

Create a custom exception class `InsufficientStockException extends \Exception` that stores how many items were `requested` and how many were `available`, with getter methods `getRequested()` and `getAvailable()`. Write a function `placeOrder(int $stock, int $requested): void` that throws this exception if `$requested` is greater than `$stock`. Write calling code with a `try`/`catch` that prints a message like `"Cannot order 5, only 2 in stock"` using the exception's getters.

## Solution

```php
<?php

class InsufficientStockException extends \Exception
{
    public function __construct(
        string $message,
        private readonly int $requested,
        private readonly int $available
    ) {
        parent::__construct($message);
    }

    public function getRequested(): int
    {
        return $this->requested;
    }

    public function getAvailable(): int
    {
        return $this->available;
    }
}

function placeOrder(int $stock, int $requested): void
{
    if ($requested > $stock) {
        throw new InsufficientStockException(
            "Not enough stock available",
            $requested,
            $stock
        );
    }

    echo "Order placed for {$requested} item(s)." . PHP_EOL;
}

try {
    placeOrder(2, 5);
} catch (InsufficientStockException $e) {
    echo "Cannot order {$e->getRequested()}, only {$e->getAvailable()} in stock" . PHP_EOL;
}
```

The custom exception stores both numbers at the moment it is thrown, so the `catch` block does not need any extra context passed around separately — everything needed to build a helpful message is already inside the exception object itself, retrieved with `getRequested()` and `getAvailable()`.

## Mini Quiz

```yaml
- question: "What keyword is used to raise an exception in PHP?"
  options:
    - "raise"
    - "throw"
    - "except"
    - "signal"
  answer: 1
  explanation: "PHP uses the throw keyword, followed by a new exception object, to raise an exception at a specific point in the code."
- question: "Why create a custom exception class instead of always throwing a plain \\Exception?"
  options:
    - "Plain \\Exception is deprecated and no longer works in PHP 8"
    - "Custom exceptions run faster than the built-in Exception class"
    - "It lets calling code catch and react to specific, meaningful problem types instead of guessing from a generic message"
    - "PHP requires every thrown object to have a unique class name"
  answer: 2
  explanation: "Custom exception classes let calling code catch a precise, named type of problem (like UserNotFoundException) and react accordingly, rather than parsing generic exception messages to figure out what happened."
- question: "In a custom exception's constructor, what does calling parent::__construct($message) do?"
  options:
    - "It deletes the custom properties you just defined"
    - "It ensures the built-in Exception behavior, like getMessage(), is properly set up"
    - "It automatically logs the exception to a file"
    - "It prevents the exception from ever being caught"
  answer: 1
  explanation: "Calling the parent class's constructor ensures the base Exception machinery, including getMessage(), is correctly initialized, since overriding the constructor otherwise skips that setup."
```
