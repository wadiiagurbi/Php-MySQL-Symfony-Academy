---
slug: one-to-many-and-many-to-many
track: mysql
trackTitle: "MySQL & Databases"
trackOrder: 2
chapter: relationships
chapterTitle: "Chapter 3 — Table Relationships"
chapterOrder: 3
title: "One-to-Many and Many-to-Many Relationships"
difficulty: intermediate
order: 1
estimatedMinutes: 12
tags: [mysql, relationships, joins]
---

## Learning Objective

By the end of this lesson, you will be able to distinguish one-to-many from many-to-many relationships, and know how to design a "join table" to correctly represent a many-to-many relationship in MySQL.

## Explanation

We've already used one-to-many relationships without naming them formally. **One-to-many** means one row in table A can be linked to *many* rows in table B, but each row in table B links back to only *one* row in table A. In our online store:

- One `category` can have many `products`, but each `product` belongs to exactly one `category`. (One category → many products.)
- One `user` can place many `orders`, but each `order` belongs to exactly one `user`. (One user → many orders.)

We implement one-to-many by putting a foreign key on the "many" side, pointing back to the "one" side — exactly what we did with `products.category_id` and `orders.user_id` in Chapter 1.

**Many-to-many** is different: a row in table A can relate to *many* rows in table B, **and** a row in table B can relate to *many* rows in table A. Consider orders and products in our store: one `order` can contain many `products` (a customer buys a mug and a notebook in the same order), and one `product` can appear in many different `orders` (many customers buy the same Coffee Mug over time). Neither side can hold a single foreign key pointing to the other, because neither side has just "one" match.

The solution is a **join table** (also called a "junction," "pivot," or "linking" table): a separate table whose only job is to record which rows from table A are connected to which rows from table B. Each row in the join table represents one pairing.

```
orders            order_items              products
--------          ------------------       --------
id                order_id (FK)            id
order_date        product_id (FK)          name
user_id           quantity                 price
```

Here, `order_items` is the join table. Each row says "this specific order contains this specific product, in this quantity." One order can have many rows in `order_items` (many products), and one product can appear in many rows in `order_items` (across many orders) — achieving a true many-to-many link, with the added bonus of being able to store extra information about the pairing itself, like `quantity`.

## Why It Matters

Recognizing which relationship type you're modeling is one of the most important skills in database design, because getting it wrong leads to either duplicated data or data you simply cannot represent correctly. A shopping cart, a blog post with multiple tags, a student enrolled in multiple courses, a recipe with multiple ingredients shared across many recipes — all of these are many-to-many relationships that require a join table, and this pattern shows up constantly in real applications.

In Symfony's Doctrine ORM, a `ManyToMany` association is, under the hood, implemented using precisely this join-table technique (Doctrine even calls it a "join table" in its own configuration) — understanding it here in raw SQL means Doctrine's behavior will make complete sense to you rather than feeling like magic.

## Example

Let's build the `order_items` join table for our online store, connecting `orders` and `products` in a genuine many-to-many relationship.

## Code

```sql
USE online_store;

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

## Code Explanation

- `order_id INT NOT NULL` and `product_id INT NOT NULL` are the two foreign keys that make this a join table — each row pairs one order with one product.
- `FOREIGN KEY (order_id) REFERENCES orders(id)` ensures every `order_id` in this table matches a real order; `FOREIGN KEY (product_id) REFERENCES products(id)` does the same for products.
- `quantity INT NOT NULL DEFAULT 1` is extra information about the *pairing itself* — how many units of that product were bought in that specific order. This is something a plain many-to-many link couldn't hold on either side alone, which is one of the advantages of a join table over some simpler alternatives.
- `id INT AUTO_INCREMENT PRIMARY KEY` gives the join table its own primary key, so each order-product pairing is itself uniquely identifiable (useful if you ever need to update or delete one specific line item).

## Common Mistakes

- **Trying to store a list of product IDs in a single column**, like `product_ids = "3,7,12"` inside the `orders` table. This looks like a shortcut but breaks nearly every SQL tool: you can't easily join, filter, count, or enforce foreign key integrity on a comma-separated string. Always use a proper join table instead.
- **Forgetting to add `quantity` (or similar) to the join table** and instead trying to represent "bought 3 units" by inserting the same `product_id` three separate times — this works but is wasteful and awkward to query.
- **Confusing which side is "one" and which is "many."** Ask: "can this thing have several of the other thing?" *and* "can the other thing have several of this thing?" If the answer to both is yes, it's many-to-many and needs a join table; if only one side can have "many," it's one-to-many and just needs a single foreign key.

## Best Practices

- Name join tables descriptively, ideally reflecting the relationship's meaning rather than just mashing the two table names together — `order_items` communicates intent better than a generic `orders_products`.
- Always add both foreign key constraints on a join table, and consider adding a `UNIQUE` constraint on the combination of the two foreign keys if a pairing should never be duplicated (e.g., preventing the exact same product from being added as two separate line items on one order, if that's a business rule you want).
- Add any columns describing the *relationship itself* (like `quantity`, or a `tagged_at` timestamp) directly onto the join table — this is exactly where such information belongs.

## Exercise

Our store now wants to support product tags (like "gift-worthy," "clearance," "eco-friendly"). One product can have several tags, and one tag can apply to many products — a many-to-many relationship. Design the two tables needed: a `tags` table and a join table `product_tags`.

## Solution

```sql
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE product_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    tag_id INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (tag_id) REFERENCES tags(id)
);
```

`tags` holds the list of distinct tag names, and `product_tags` is the join table pairing any product with any tag — allowing a product to have multiple tags, and a tag to be applied to multiple products.

## Mini Quiz

```yaml
- question: "Which relationship type describes 'one category has many products, but each product belongs to exactly one category'?"
  options:
    - "Many-to-many"
    - "One-to-many"
    - "One-to-one"
    - "No relationship at all"
  answer: 1
  explanation: "One category linking to many products, while each product links back to only one category, is the definition of a one-to-many relationship."

- question: "Why can't a many-to-many relationship be represented with a single foreign key on either table?"
  options:
    - "Foreign keys are not allowed in MySQL"
    - "Because both sides can be linked to multiple rows on the other side, which a single foreign key column cannot express"
    - "Because many-to-many relationships don't need any linking at all"
    - "Because MySQL tables can only have one column"
  answer: 1
  explanation: "A single foreign key column can only point to one row. Many-to-many requires each side to potentially link to many rows on the other side, which needs a separate join table."

- question: "What is the purpose of the order_items table in the online store example?"
  options:
    - "To store customer login credentials"
    - "To act as a join table linking orders and products, recording which products (and how many) are in each order"
    - "To replace the products table entirely"
    - "To store category names"
  answer: 1
  explanation: "order_items is a join table connecting orders and products in a many-to-many relationship, with an added quantity column describing each pairing."
```
