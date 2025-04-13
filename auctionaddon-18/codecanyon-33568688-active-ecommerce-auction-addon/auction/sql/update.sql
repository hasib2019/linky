CREATE TABLE `auction_product_bids` (
  `id` int(11) NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `auction_product_bids`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `auction_product_bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `products` ADD `starting_bid` double(20,2) DEFAULT '0.00' AFTER `discount_end_date`;
ALTER TABLE `products` ADD `auction_start_date` INT(11) DEFAULT NULL AFTER `starting_bid`;
ALTER TABLE `products` ADD `auction_end_date` INT(11) DEFAULT NULL AFTER `auction_start_date`;

COMMIT;
