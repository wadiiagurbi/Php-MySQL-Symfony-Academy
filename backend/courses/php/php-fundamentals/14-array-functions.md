---
slug: array-functions
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Useful Array Functions"
difficulty: beginner
order: 14
estimatedMinutes: 15
tags: [php, arrays, functions]
---

## Learning Objective

By the end of this lesson, you will be able to use several of PHP's most common
built-in array functions — `count()`, `array_push()`, `array_pop()`, `in_array()`,
`array_search()`, `sort()`, `rsort()`, `array_merge()`, `array_map()`, and
`array_filter()` — to inspect, search, reorder, combine, and transform arrays
without writing loops by hand for every single task.

## Explanation

Over the last two lessons, you learned how to build arrays and loop over them
yourself using `for` and `foreach`. That is an essential skill, but PHP also
includes hundreds of **built-in functions** that already do many common,
everyday array tasks for you. A function, in general, is simply a named, reusable
block of code that you can "call" (run) by writing its name followed by
parentheses containing whatever input it needs. You have already been calling
functions without necessarily using that word: `count($array)` and
`isset($array["key"])` are both functions. This lesson introduces several more,
focused specifically on arrays.

Here are the ones you will use in this lesson:

- **`count($array)`** returns the number of elements in an array. You already met
  this in an earlier lesson.
- **`array_push($array, $value)`** adds one or more new values onto the end of an
  array. It does the same job as `$array[] = $value;`, and in everyday PHP code,
  the `$array[] = $value;` shorthand is actually used more often — but
  `array_push()` is worth knowing since you will see it in other people's code.
- **`array_pop($array)`** removes the *last* element from an array and returns
  it, shrinking the array by one element.
- **`in_array($value, $array)`** checks whether a given value exists anywhere in
  an array, returning `true` or `false`.
- **`array_search($value, $array)`** looks for a value in an array and returns
  the *key* (or index) where it was found, or `false` if it was not found anywhere.
- **`sort($array)`** rearranges an array's elements into ascending order (smallest
  to largest, or alphabetical for strings), and re-numbers the indexes starting
  from 0. **`rsort($array)`** does the same thing but in descending order (largest
  to smallest).
- **`array_merge($array1, $array2)`** combines two (or more) arrays together into
  one new array, one after another.
- **`array_map($callback, $array)`** builds a *new* array by applying the same
  transformation to every element of an existing array, without changing the
  original array. The transformation itself is written as a small, unnamed
  function called an **anonymous function** (or **arrow function**, using the
  short `fn` syntax), which you write inline, right where it is needed:
  `fn($item) => $item * 2` describes "take one item and return double its value."
  You will learn how to write your own named functions properly very soon — for
  now, just recognize this shape as "a small transformation rule passed to
  another function."
- **`array_filter($callback, $array)`** (note that with `array_filter`, the
  callback actually comes *first* as the array and second as the test — PHP's
  real signature is `array_filter($array, $callback)`) builds a *new* array
  containing only the elements for which the callback returns `true`, discarding
  the rest.

## Why It Matters

Writing a hand-rolled loop to search for a value, sort a list, or transform every
element works, but it is repetitive and easy to get subtly wrong (off-by-one
errors, forgetting to handle an empty array, and so on). PHP's built-in array
functions have already been written, tested, and optimized by the language's
maintainers, so using them makes your code shorter, more reliable, and often
easier for other developers to understand at a glance, since function names like
`in_array()` and `sort()` directly describe their intent.

In real backend code, these functions show up constantly: checking `in_array()`
to see if a user's role is allowed to perform an action, `sort()`-ing a list of
search results by relevance, `array_merge()`-ing default settings with a user's
custom settings, `array_filter()`-ing out invalid or incomplete records before
saving them to a database, and `array_map()`-ping over a list of database rows to
reshape them into the exact format an API response needs. Knowing this toolkit
well means you will reach for the right built-in function instead of
reinventing it every time — which is exactly what experienced PHP developers do.

## Example

Imagine a small online shopping cart holding a list of item prices, but a bug
elsewhere in the checkout system sometimes puts an invalid price of `0` into the
list by mistake. You want to: remove the invalid `0` prices, apply a 10% discount
to every remaining price, sort the discounted prices from cheapest to most
expensive, and calculate the total. Along the way, you also want to check whether
a specific price is in the cart, and combine this cart with prices from a second
cart.

## Code

```php
<?php

$cartPrices = [25.00, 0, 40.00, 15.50, 0, 60.00];

echo "Original item count: " . count($cartPrices) . "\n";

// Remove invalid ($0) prices, keeping only real ones
$validPrices = array_filter($cartPrices, fn($price) => $price > 0);

echo "Valid item count: " . count($validPrices) . "\n";

// Apply a 10% discount to every remaining price
$discountedPrices = array_map(fn($price) => $price * 0.9, $validPrices);

// Sort the discounted prices from cheapest to most expensive
sort($discountedPrices);

echo "Discounted prices, sorted:\n";
foreach ($discountedPrices as $price) {
    echo "$" . round($price, 2) . "\n";
}

// Check whether a specific price is present, and find its position
if (in_array(40.00, $cartPrices)) {
    echo "There is a $40 item in the original cart.\n";
}

$position = array_search(15.50, $cartPrices);
echo "The $15.50 item was originally at position " . $position . "\n";

// Merge with a second cart, then remove the last item added
$secondCart = [10.00, 22.00];
$combinedCart = array_merge($cartPrices, $secondCart);
array_pop($combinedCart);

echo "Combined cart item count after removing the last item: " .
    count($combinedCart) . "\n";
```

## Code Explanation

- `$cartPrices` is an indexed array with six prices, two of which are the invalid
  value `0`.
- `array_filter($cartPrices, fn($price) => $price > 0)` walks through
  `$cartPrices` and keeps only the elements for which the small anonymous function
  `fn($price) => $price > 0` returns `true`. Since `0 > 0` is `false`, both invalid
  prices are dropped, leaving `$validPrices` with four elements.
- `array_map(fn($price) => $price * 0.9, $validPrices)` builds a brand new array
  where every price from `$validPrices` has been multiplied by `0.9` (a 10%
  discount), without changing `$validPrices` itself.
- `sort($discountedPrices)` rearranges `$discountedPrices` in place, from smallest
  to largest, and renumbers its indexes starting at 0.
- The `foreach` loop then prints each sorted, discounted price, using `round($price,
  2)` to keep only two decimal places for a clean dollar amount.
- `in_array(40.00, $cartPrices)` searches the *original* cart for the value
  `40.00` and returns `true` because it is present, so the message prints.
- `array_search(15.50, $cartPrices)` searches for `15.50` in the original cart and
  returns its index, which is `3` (counting from 0: `25.00` is 0, `0` is 1, `40.00`
  is 2, `15.50` is 3).
- `array_merge($cartPrices, $secondCart)` produces one combined array containing
  all six original prices followed by the two prices from `$secondCart`, giving
  eight elements in total.
- `array_pop($combinedCart)` removes the very last element (`22.00`) from
  `$combinedCart`, shrinking it to seven elements, which is what the final `count()`
  reports.

## Common Mistakes

- **Mixing up the argument order of `array_filter()`.** Unlike `array_map()`,
  where the callback comes first, `array_filter()` takes the array *first* and the
  callback *second*: `array_filter($array, $callback)`. Writing them backwards is
  a very common beginner mistake.
- **Forgetting that `sort()` re-indexes the array.** After calling `sort()`, the
  original keys are replaced with fresh, sequential numeric indexes starting from
  0. If you were relying on the original indexes meaning something specific, they
  will be gone after sorting.
- **Expecting `array_map()` or `array_filter()` to change the original array.**
  Both functions return a brand new array; they do not modify the array you passed
  in. If you want to keep the transformed result, you must assign it to a
  variable, as shown in the example (`$validPrices = array_filter(...)`).
- **Using `in_array()` when you actually need the position, or `array_search()`
  when you only need a yes/no answer.** `in_array()` returns a plain `true`/`false`
  and cannot tell you *where* a value is; `array_search()` returns the position (or
  `false` if not found), which can behave unexpectedly in comparisons since `0` is
  a valid position but can look "falsy" if you are not careful — always compare its
  result with `===` against `false` rather than just checking `if (!$position)`.

## Best Practices

- Reach for a built-in array function before writing a manual loop when one
  already exists for the task — it is usually shorter, clearer, and less
  error-prone than reinventing the same logic yourself.
- When chaining several array functions together, as in the example (`array_filter`
  then `array_map` then `sort`), give each intermediate result its own clearly
  named variable (`$validPrices`, `$discountedPrices`) instead of nesting the calls
  together on one unreadable line.
- Remember that `array_map()` and `array_filter()` return new arrays rather than
  modifying the original — this is a deliberate design that helps prevent
  accidentally losing or corrupting your original data.
- When comparing the result of `array_search()`, always use `===` (or `!==`) to
  check specifically against `false`, since a valid found position can be `0`,
  which many looser comparisons would incorrectly treat as "not found."

## Exercise

Given the array `$ages = [15, 22, 17, 30, 16, 45];`, write code that: filters out
every age under 18 (keeping only adults), sorts the remaining ages from oldest to
youngest, and prints whether the age `30` exists anywhere in the original
`$ages` array.

## Solution

```php
<?php

$ages = [15, 22, 17, 30, 16, 45];

$adultAges = array_filter($ages, fn($age) => $age >= 18);

rsort($adultAges); // sort descending: oldest to youngest

echo "Adult ages, oldest to youngest:\n";
foreach ($adultAges as $age) {
    echo $age . "\n";
}

if (in_array(30, $ages)) {
    echo "Age 30 is present in the original list.\n";
} else {
    echo "Age 30 is not present in the original list.\n";
}
```

`array_filter($ages, fn($age) => $age >= 18)` keeps only the ages that are 18 or
older (`22`, `30`, `45`), discarding `15`, `17`, and `16`. `rsort($adultAges)`
then reorders those remaining ages from largest to smallest, producing `45`, `30`,
`22`. Finally, `in_array(30, $ages)` checks the *original*, unfiltered `$ages`
array and returns `true` since `30` was one of the original values, so the first
message prints.

## Mini Quiz

```yaml
- question: "What is the correct argument order for array_filter()?"
  options:
    - "array_filter($callback, $array)"
    - "array_filter($array, $callback)"
    - "array_filter($array)"
    - "The order does not matter"
  answer: 1
  explanation: "array_filter() takes the array first and the callback function second, which is the opposite order from array_map()."
- question: "After calling sort($numbers) on an indexed array, what happens to the array's keys?"
  options:
    - "The original keys are preserved exactly as they were"
    - "The array becomes associative"
    - "The keys are replaced with fresh, sequential numeric indexes starting at 0"
    - "sort() does not change the keys, only the order printed by foreach"
  answer: 2
  explanation: "sort() rearranges the values into ascending order and re-numbers the array's keys from 0 upward, discarding the original index positions."
- question: "What does array_map() return?"
  options:
    - "A single transformed value"
    - "A new array with the transformation applied to each element, leaving the original array unchanged"
    - "The original array, modified in place"
    - "A boolean indicating whether the transformation succeeded"
  answer: 1
  explanation: "array_map() builds and returns a brand new array containing the results of applying the given transformation to every element, without modifying the original array."
- question: "Why should array_search() results be compared with === false rather than just checked with if (!$result)?"
  options:
    - "Because array_search() never returns false"
    - "Because a found position of 0 would otherwise be mistaken for 'not found'"
    - "Because === is required by PHP syntax rules for all comparisons"
    - "Because array_search() returns a string, not a number"
  answer: 1
  explanation: "array_search() can validly return 0 as a found position, and a loose check like if (!$result) would treat 0 as false, incorrectly suggesting the value was not found."
```
