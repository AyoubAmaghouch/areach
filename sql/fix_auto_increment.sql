-- =============================================================================
-- AREACH — AwardSpace MySQL 8 schema fix
--
-- Problem:
--   After importing the local MySQL/MariaDB dump, several transactional tables
--   lost their AUTO_INCREMENT flag on their primary-key column (typically
--   because the dump contained "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO').
--
--   This caused checkout INSERTs into `order_items` to omit the table PK in
--   the column list, but MySQL filled it with the column's implicit default
--   (0) on every row. The second checkout attempt then failed with:
--     SQLSTATE[23000]: Integrity constraint violation: 1062
--     Duplicate entry '0' for key 'order_items.PRIMARY'
--
-- Fix:
--   Restore AUTO_INCREMENT on the single-column primary key of every
--   transactional table. Run this in phpMyAdmin (SQL tab) on the
--   AwardSpace database `4772751_areach` once. It is idempotent — running
--   it again does nothing harmful.
--
-- IMPORTANT: replace this block with the *actual* column names; they are
--   inferred here from the PHP code's queries:
--     - orders           -> id_order     (PK referenced in checkout.php:140)
--     - order_items      -> id_item      (PK referenced in checkout.php:173)
--     - customers        -> id_customer  (PK referenced in checkout.php:340)
--     - products         -> id_product   (PK referenced in includes/functions.php)
--     - product_variants -> id_variant   (PK referenced in checkout.php:160)
--     - product_images   -> id_image     (PK referenced in checkout.php:166)
--
--   If a table's PK has a different name, the query will fail safely (no
--   unknown-column error will corrupt data) — adjust the column name and
--   run again.
-- =============================================================================

-- 1. orders — restore AUTO_INCREMENT on id_order
ALTER TABLE `orders`
    MODIFY `id_order` INT NOT NULL AUTO_INCREMENT;

-- 2. order_items — restore AUTO_INCREMENT on id_item (fixes the checkout bug)
ALTER TABLE `order_items`
    MODIFY `id_item` INT NOT NULL AUTO_INCREMENT;

-- 3. customers — restore AUTO_INCREMENT on id_customer
ALTER TABLE `customers`
    MODIFY `id_customer` INT NOT NULL AUTO_INCREMENT;

-- 4. products — restore AUTO_INCREMENT on id_product
ALTER TABLE `products`
    MODIFY `id_product` INT NOT NULL AUTO_INCREMENT;

-- 5. product_variants — restore AUTO_INCREMENT on id_variant
ALTER TABLE `product_variants`
    MODIFY `id_variant` INT NOT NULL AUTO_INCREMENT;

-- 6. product_images — restore AUTO_INCREMENT on id_image
--    (Already AUTO_INCREMENT in the dump shipped with the project, but
--    included here for completeness in case a re-import drops it.)
ALTER TABLE `product_images`
    MODIFY `id_image` INT NOT NULL AUTO_INCREMENT;

-- =============================================================================
-- Optional: reset the AUTO_INCREMENT counter to a value safely above any
-- existing row's primary-key value, so MySQL never tries to reuse an id
-- already in the table. Replace <next_id> with the correct value (max(id)+1)
-- for each table — these queries are commented out by default.
-- =============================================================================
-- ALTER TABLE `orders`          AUTO_INCREMENT = <max_id_order + 1>;
-- ALTER TABLE `order_items`     AUTO_INCREMENT = <max_id_item + 1>;
-- ALTER TABLE `customers`       AUTO_INCREMENT = <max_id_customer + 1>;
-- ALTER TABLE `products`        AUTO_INCREMENT = <max_id_product + 1>;
-- ALTER TABLE `product_variants` AUTO_INCREMENT = <max_id_variant + 1>;
-- ALTER TABLE `product_images`  AUTO_INCREMENT = <max_id_image + 1>;
