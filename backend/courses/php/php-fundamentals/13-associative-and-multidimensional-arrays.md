---
slug: associative-and-multidimensional-arrays
track: php
trackTitle: "PHP"
trackOrder: 1
chapter: php-fundamentals
chapterTitle: "Chapter 1 — PHP Fundamentals"
chapterOrder: 1
title: "Associative and Multidimensional Arrays"
difficulty: beginner
order: 13
estimatedMinutes: 14
tags: [php, arrays, data-structures]
---

## Learning Objective

By the end of this lesson, you will understand what an associative array is and
how it differs from the indexed arrays you already know, how to check whether a
key exists, how to loop over key-value pairs with `foreach`, and how to build and
use a multidimensional array (an array made up of other arrays).

## Explanation

In the previous lesson, you learned that indexed arrays store their elements at
automatic, numeric positions: `0`, `1`, `2`, and so on. That works well when the
*order* of elements is what matters. But sometimes, a number is not a meaningful
way to refer to a piece of data. If you are storing information about a single
product — its name, its price, its quantity in stock — referring to those pieces
of data as `$product[0]`, `$product[1]`, and `$product[2]` would force you to
memorize which number means what, which is confusing and easy to get wrong.

This is exactly the problem **associative arrays** solve. Instead of automatic
numeric indexes, an associative array lets you choose your own **keys**, which are
usually short, descriptive strings, and each key points to a corresponding
**value**. You create one using the `=>` symbol (sometimes read as "maps to")
between each key and its value:

```
$product = [
    "name" => "Keyboard",
    "price" => 49.99,
    "quantity" => 12,
];
```

Here, `"name"`, `"price"`, and `"quantity"` are keys, and `"Keyboard"`, `49.99`,
and `12` are their corresponding values.

To read a value, you use the key inside square brackets, just like with an indexed
array, except the key is a string instead of a number: `$product["price"]` gives
you `49.99`. To change a value, you assign to that same key: `$product["quantity"]
= 15;`. If the key does not exist yet, PHP simply creates it: `$product["sku"] =
"KB-100";` adds a brand new key-value pair to the array.

Sometimes you need to check whether a key exists *before* trying to use it,
especially if the array's contents might vary. PHP gives you two functions for
this: `isset($product["color"])` returns `true` only if the key `"color"` exists
*and* its value is not `null`, while `array_key_exists("color", $product)` returns
`true` if the key exists at all, even if its value happens to be `null`. For most
everyday checks, `isset()` is the more commonly used of the two.

To loop over an associative array, `foreach` supports a special form that gives you
both the key and the value on each iteration:

```
foreach ($product as $key => $value) {
    echo $key . ": " . $value . "\n";
}
```

Finally, arrays can hold *any* kind of value as their elements — including other
arrays. An array whose elements are themselves arrays is called a
**multidimensional array**. This is extremely useful for representing a *list of
records*, such as several products, where each individual product is an
associative array, and all of those associative arrays are collected together
inside one outer indexed array:

```
$products = [
    ["name" => "Keyboard", "price" => 49.99],
    ["name" => "Mouse", "price" => 19.99],
];
```

Here, `$products` is an indexed array (index `0`, index `1`, ...), and each element
inside it is itself an associative array. You can reach a specific product's name
by chaining the access together: `$products[0]["name"]` gives you `"Keyboard"`.

## Why It Matters

Associative arrays and multidimensional arrays are the backbone of how real
applications represent structured data. A user profile isn't just one value — it
has a name, an email, an age, and more, which is naturally modeled as an
associative array with keys like `"name"` and `"email"`. A list of blog posts,
search results, or products is naturally modeled as a multidimensional array: a
list (indexed array) of records (associative arrays).

This matters enormously in a Symfony backend: when your code fetches rows from a
database, PHP typically hands them back to you as exactly this shape — an indexed
array of associative arrays, one per row, with column names as keys. When your
application receives data from an API as JSON and decodes it, or when it prepares
data to send back as JSON, it is almost always working with associative and
multidimensional arrays under the hood. Understanding this shape now means the
data you will work with throughout the rest of this course, and in real Symfony
projects, will already feel familiar.

## Example

Imagine a very small "database" of a shop's products, kept directly in PHP for
now, where each product has a name, a price, and a quantity in stock. You want to
print a receipt-like list showing each product's details, and separately calculate
the total value of all the stock combined (price multiplied by quantity, summed
across every product).

## Code

```php
<?php

$products = [
    ["name" => "Keyboard", "price" => 49.99, "quantity" => 12],
    ["name" => "Mouse", "price" => 19.99, "quantity" => 30],
    ["name" => "Monitor", "price" => 129.50, "quantity" => 5],
];

$totalValue = 0;

foreach ($products as $product) {
    $lineValue = $product["price"] * $product["quantity"];
    $totalValue = $totalValue + $lineValue;

    echo $product["name"] . ": " . $product["quantity"] . " units at $" .
        $product["price"] . " each\n";
}

echo "Total stock value: $" . $totalValue . "\n";

// Checking whether a key exists before using it
if (isset($products[0]["sku"])) {
    echo "First product has a SKU.\n";
} else {
    echo "First product has no SKU defined.\n";
}
```

## Code Explanation

- `$products` is an indexed array with three elements (indexes `0`, `1`, `2`). Each
  of those elements is itself an associative array with the keys `"name"`,
  `"price"`, and `"quantity"` — this is the multidimensional structure described
  above.
- `foreach ($products as $product)` loops over the *outer* array. On each
  iteration, `$product` holds one complete associative array — for example, on the
  first pass, `$product` is `["name" => "Keyboard", "price" => 49.99, "quantity" =>
  12]`.
- Inside the loop, `$product["price"]` and `$product["quantity"]` read values out
  of the *current* product's associative array using their string keys.
  `$lineValue = $product["price"] * $product["quantity"];` multiplies price by
  quantity to get that one product's total stock value.
- `$totalValue = $totalValue + $lineValue;` accumulates each product's line value
  into a running grand total across all iterations of the loop.
- The `echo` line inside the loop concatenates several pieces together, including
  the product's name, quantity, and price, to print a readable line for each
  product.
- After the loop finishes, the final `echo` outside the loop prints the completed
  `$totalValue`, which is the sum of all three products' stock values.
- `isset($products[0]["sku"])` checks two things at once, from the outside in:
  first it looks at index `0` of `$products` (the Keyboard's associative array),
  then it checks whether that array has a key called `"sku"`. Since none of the
  products were given a `"sku"` key, this returns `false`, so the `else` branch
  runs.

## Common Mistakes

- **Using a numeric index on an associative array, or a string key on an indexed
  array, and expecting it to work the same way.** `$product["price"]` and
  `$product[0]` are only interchangeable if the array actually has both a key
  `"price"` and a key `0` — mixing them up when it does not will produce a warning
  or an unexpected value.
- **Forgetting quotes around string keys**, writing `$product[name]` instead of
  `$product["name"]`. Without quotes, PHP tries to interpret `name` as a constant
  or produces an error, rather than treating it as the string key you intended.
- **Accessing a key that might not exist without checking first**, which produces
  a warning and returns `null`. Use `isset()` before relying on a key that might be
  missing, especially with data coming from outside your own code (like user input
  or an external API).
- **Confusing the two levels of a multidimensional array.** Writing
  `$products["name"]` when you meant `$products[0]["name"]` is a common mistake —
  remember that the outer array is indexed (by position), while each individual
  product inside it is associative (by key).

## Best Practices

- Use associative arrays whenever a piece of data has multiple *named* attributes,
  and indexed (or multidimensional) arrays whenever you have a *list* of similar
  things, whether simple values or full records.
- Use `isset()` to guard against missing keys whenever the data might come from an
  unpredictable source, such as user-submitted forms or an external API response.
- When looping with `foreach ($array as $key => $value)`, choose variable names for
  `$key` and `$value` that describe what they actually represent (for example,
  `$productName => $stock` instead of generic `$key => $value`), which makes the
  loop body much easier to read.
- Keep the "shape" of each record in a multidimensional array consistent — if one
  product has a `"quantity"` key, make sure every product in that list has one too
  (even if it is `0`), to avoid needing constant `isset()` checks everywhere you use
  the data.

## Exercise

Create a multidimensional array of three students, where each student is an
associative array with keys `"name"` and `"grade"` (a number out of 100). Loop
over the students and print each one's name and grade. Then calculate and print
the average grade across all three students.

## Solution

```php
<?php

$students = [
    ["name" => "Amina", "grade" => 88],
    ["name" => "Liam", "grade" => 76],
    ["name" => "Yuki", "grade" => 95],
];

$totalGrade = 0;

foreach ($students as $student) {
    echo $student["name"] . ": " . $student["grade"] . "\n";
    $totalGrade = $totalGrade + $student["grade"];
}

$average = $totalGrade / count($students);

echo "Average grade: " . $average . "\n";
```

`$students` is an indexed array holding three associative arrays, each with a
`"name"` key and a `"grade"` key. The `foreach` loop visits each student in turn,
printing their name and grade and adding the grade onto `$totalGrade`. After the
loop, dividing `$totalGrade` by `count($students)` (which is `3`) produces the
average grade of `(88 + 76 + 95) / 3 = 86.33...`.

## Mini Quiz

```yaml
- question: "How do you create a key-value pair in a PHP associative array?"
  options:
    - "Using a colon, like \"name\": \"Keyboard\""
    - "Using the => symbol, like \"name\" => \"Keyboard\""
    - "Using an equals sign only, like \"name\" = \"Keyboard\""
    - "Associative arrays cannot have custom keys in PHP"
  answer: 1
  explanation: "PHP associative arrays use the => symbol to connect each key to its value, for example \"name\" => \"Keyboard\"."
- question: "Given $product[0][\"name\"], what does this expression describe?"
  options:
    - "A single associative array with a key called 0"
    - "The value at key \"name\" inside the associative array stored at index 0 of $product"
    - "An error, because arrays cannot be combined this way"
    - "The name of the variable $product itself"
  answer: 1
  explanation: "This is a multidimensional array access: first PHP looks up index 0 of the outer indexed array, then it looks up the key \"name\" inside that inner associative array."
- question: "What does isset($data[\"email\"]) check?"
  options:
    - "Whether $data is an array at all"
    - "Whether the key \"email\" exists in $data and its value is not null"
    - "Whether $data has more than one element"
    - "Whether \"email\" is spelled correctly in the code"
  answer: 1
  explanation: "isset() returns true only when the given key exists in the array and its value is not null."
- question: "When you write foreach ($product as $key => $value), what does $key represent on each iteration?"
  options:
    - "The numeric position of the element, always starting at 0"
    - "The array's variable name"
    - "The key (name) associated with the current element"
    - "The total number of elements in the array"
  answer: 2
  explanation: "In the $key => $value form of foreach, $key holds the key (whether numeric or string) of the current element, and $value holds its corresponding value."
```
