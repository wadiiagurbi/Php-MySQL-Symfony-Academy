---
slug: indexed-arrays
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Indexed Arrays"
difficulty: beginner
order: 12
estimatedMinutes: 12
tags: [php, arrays, data-structures]
---

## Learning Objective

By the end of this lesson, you will understand what an array is, how to create an
indexed array, how to read and change its values, how to add new elements, and how
to loop over an array's elements using both `foreach` and a numeric `for` loop.

## Explanation

Every variable you have used so far has held exactly one value: a single number, a
single piece of text, or a single `true`/`false`. But real programs often need to
work with *lists* of things: a list of product names in a store, a list of scores
in a class, a list of comments on a post. Storing each item in its own separate
variable (`$score1`, `$score2`, `$score3`, ...) would be clumsy and would not scale
to a list of unknown or changing size.

PHP solves this with **arrays**. An array is a single variable that can hold
multiple values at once, stored in an ordered list. Each value inside the array is
called an **element**.

The simplest kind of array is an **indexed array**, where every element is
automatically assigned a position number called an **index**. PHP arrays are
**zero-based**, which means the first element's index is `0`, not `1`. The second
element's index is `1`, the third is `2`, and so on.

You create an indexed array using square brackets `[]`, listing the values
separated by commas:

```
$scores = [85, 92, 78];
```

Here, `$scores[0]` is `85`, `$scores[1]` is `92`, and `$scores[2]` is `78`.

To read a specific element, you write the array's variable name followed by the
index in square brackets, for example `$scores[1]`. To change an element, you
assign a new value the same way: `$scores[1] = 100;`.

To find out how many elements an array currently holds, PHP provides a built-in
function called `count()`. Calling `count($scores)` returns the number of elements
— in the example above, `3`.

To add a brand new element to the end of an array, you use empty square brackets on
the left side of an assignment: `$scores[] = 60;`. PHP automatically figures out
the next available index and puts the new value there.

Finally, to go through every element in an array one at a time, PHP provides the
`foreach` loop, which you first heard about in the loops lesson. Its shape is:

```
foreach ($scores as $score) {
    // $score holds one element on each iteration
}
```

`foreach` automatically visits every element in order, from first to last, without
you needing to manage an index yourself. You can also use a regular numeric `for`
loop together with `count()` to achieve the same result when you specifically need
access to the index number.

## Why It Matters

Arrays are one of the most heavily used tools in everyday programming because
almost all real data comes in lists. A product catalog is a list of products. A
search result page is a list of matches. An invoice is a list of line items. Every
one of these is naturally represented as an array in code.

In a Symfony backend, you will constantly receive arrays: a database query returns
rows as an array, a submitted web form's checkboxes arrive as an array of selected
values, and a JSON API response is frequently built directly from an array. Being
comfortable creating, reading, updating, and looping over arrays is a foundation
you will rely on in nearly every feature you build from here on, so it is worth
taking the time to get genuinely comfortable with the basics before moving on.

## Example

Imagine a small online store that wants to display a numbered list of three
product names, print how many products it has, add a new product to the end of the
list, and then update the price... but first, just the names. This is a perfect
case for an indexed array: an ordered list of values, accessed and looped over by
position.

## Code

```php
<?php

$products = ["Keyboard", "Mouse", "Monitor"];

echo "We have " . count($products) . " products.\n";

// Access elements by index (zero-based!)
echo "First product: " . $products[0] . "\n";
echo "Last product: " . $products[2] . "\n";

// Modify an existing element
$products[1] = "Wireless Mouse";

// Add a new element to the end
$products[] = "Webcam";

echo "We now have " . count($products) . " products.\n";

// Loop over every element with foreach
foreach ($products as $product) {
    echo "- " . $product . "\n";
}

// Loop over every element with a numeric for loop instead
for ($i = 0; $i < count($products); $i++) {
    echo $i . ": " . $products[$i] . "\n";
}
```

## Code Explanation

- `$products = ["Keyboard", "Mouse", "Monitor"];` creates an indexed array with
  three elements. PHP assigns index `0` to `"Keyboard"`, `1` to `"Mouse"`, and `2`
  to `"Monitor"` automatically.
- `count($products)` returns `3`, the current number of elements, which gets
  concatenated into the first `echo` line.
- `$products[0]` retrieves the element at index 0, which is `"Keyboard"` — the
  *first* element, even though its index is 0, not 1. `$products[2]` retrieves the
  *third* (and here, last) element, `"Monitor"`.
- `$products[1] = "Wireless Mouse";` overwrites the element at index 1. It no
  longer matters that it used to hold `"Mouse"` — the array now holds `"Wireless
  Mouse"` at that position.
- `$products[] = "Webcam";` appends a brand new element to the end of the array.
  PHP notices the highest existing index is 2, so it automatically places
  `"Webcam"` at index 3. After this line, `count($products)` becomes `4`.
- `foreach ($products as $product) { ... }` visits every element in the array, in
  order, assigning the current element's value to `$product` on each iteration. The
  loop body then prints each product name preceded by a dash.
- The final `for` loop achieves the same result manually: `$i` starts at 0 and
  increases by 1 each time, while `$i < count($products)` stays true. On each
  iteration, `$products[$i]` retrieves the element at the current position, letting
  us print both the index and the value.

## Common Mistakes

- **Assuming the first element's index is 1.** PHP arrays are zero-based, so the
  first element is always at index `0`. Trying to access `$products[3]` on a
  four-element array (indexes 0, 1, 2, 3) is valid, but `$products[4]` would not
  exist and would cause a warning.
- **Using `<=` instead of `<` in a numeric for loop over an array.** Since indexes
  run from `0` to `count($array) - 1`, the correct condition is `$i <
  count($products)`, not `$i <= count($products)`. Using `<=` would try to access
  one index past the end of the array, which does not exist.
- **Forgetting the empty brackets when appending.** Writing `$products = "Webcam";`
  instead of `$products[] = "Webcam";` would completely overwrite the whole array
  with a single string, destroying every existing element.
- **Recalculating `count()` unnecessarily inside a large loop** without realizing
  it, for example calling `count($products)` on every single iteration inside the
  loop's body when it only needs to be checked as part of the loop's condition
  (which PHP already re-evaluates automatically on every pass).

## Best Practices

- Prefer `foreach` whenever you just need each value in order and do not care about
  the index number — it is shorter, clearer, and eliminates the risk of an
  off-by-one mistake entirely.
- Use a numeric `for` loop specifically when you need the index itself, for example
  to print "item number 3" or to compare neighboring elements.
- Give array variables plural, descriptive names (`$products`, `$scores`,
  `$usernames`) so it is immediately clear from the name alone that the variable
  holds a list, not a single value.
- Check `count($array)` before assuming an array has a particular element,
  especially when the array's size might change based on user input or data from a
  database.

## Exercise

Create an indexed array of four student exam scores: 88, 95, 73, and 60. Print the
total number of scores. Then use a `foreach` loop to print every score, and
separately use a numeric `for` loop to calculate and print the sum of all the
scores.

## Solution

```php
<?php

$scores = [88, 95, 73, 60];

echo "Number of scores: " . count($scores) . "\n";

foreach ($scores as $score) {
    echo "Score: " . $score . "\n";
}

$total = 0;

for ($i = 0; $i < count($scores); $i++) {
    $total = $total + $scores[$i];
}

echo "Total: " . $total . "\n";
```

The array `$scores` holds four elements at indexes 0 through 3. `count($scores)`
reports `4`. The `foreach` loop visits each score in order and prints it without
needing to track an index. The numeric `for` loop then walks through indexes `0` up
to (but not including) `count($scores)`, adding each score onto `$total` as it
goes, ending with `$total` equal to `88 + 95 + 73 + 60 = 316`.

## Mini Quiz

```yaml
- question: "What is the index of the first element in a PHP indexed array?"
  options:
    - "1"
    - "0"
    - "-1"
    - "It depends on how the array was created"
  answer: 1
  explanation: "PHP arrays are zero-based, so the very first element is always stored at index 0."
- question: "Which line correctly appends a new element to the end of the array $items?"
  options:
    - "$items[0] = \"new\";"
    - "$items = \"new\";"
    - "$items[] = \"new\";"
    - "append($items, \"new\");"
  answer: 2
  explanation: "Using empty square brackets, $items[] = \"new\";, tells PHP to automatically add the value at the next available index at the end of the array."
- question: "If $colors = [\"red\", \"green\", \"blue\"];, what does count($colors) return?"
  options:
    - "2"
    - "3"
    - "4"
    - "It cannot be determined without running the code"
  answer: 1
  explanation: "The array has three elements (\"red\", \"green\", \"blue\"), so count($colors) returns 3."
- question: "In a numeric for loop used to visit every element of an array by index, which condition is correct?"
  options:
    - "$i <= count($array)"
    - "$i < count($array)"
    - "$i == count($array)"
    - "$i > count($array)"
  answer: 1
  explanation: "Valid indexes run from 0 up to count($array) - 1, so the loop should continue only while $i is strictly less than count($array)."
```
