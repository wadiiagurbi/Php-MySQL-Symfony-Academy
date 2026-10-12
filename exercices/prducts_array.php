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