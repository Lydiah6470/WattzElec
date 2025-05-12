-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2025 at 10:25 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wattzelec`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `name`, `description`, `image`, `created_at`) VALUES
(1, 'Appliances ', 'Task driven machines for daily use', NULL, '2025-04-20 22:33:50'),
(2, 'Electronics', 'Tech-driven devices for communication and entertainment', NULL, '2025-04-20 22:33:50'),
(3, 'Accessories', 'supplementary items that enhance or complement electronics and appliances.', NULL, '2025-04-20 22:34:46');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_id` int(11) NOT NULL,
  `shipping_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 3, 1, 4750.00),
(2, 2, 4, 1, 2250.00),
(3, 3, 1, 1, 300000.00),
(4, 4, 1, 1, 300000.00),
(5, 5, 4, 1, 2250.00),
(6, 6, 3, 1, 4750.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(5,2) DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `status` enum('in_stock','out_of_stock','discontinued') DEFAULT 'in_stock',
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_1` varchar(255) DEFAULT NULL,
  `image_2` varchar(255) DEFAULT NULL,
  `image_3` varchar(255) DEFAULT NULL,
  `final_price` decimal(10,2) GENERATED ALWAYS AS (`price` * (1 - coalesce(`discount`,0) / 100)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `subcategory_id`, `name`, `description`, `price`, `discount`, `stock_quantity`, `status`, `featured`, `created_at`, `updated_at`, `image_1`, `image_2`, `image_3`) VALUES
(1, 5, 'Samsung Side by Side Refrigerator RS64R5311B4 617L', 'Side-by-Side Design: Provides separate compartments for the refrigerator and freezer, allowing for easy access to both fresh and frozen items.\r\n617-Liter Capacity: Offers ample storage space suitable for large households or those who require plenty of refrigeration and freezing capacity.\r\nGentle Black Finish: Boasts a stylish Gentle Black exterior finish that adds a touch of elegance to any kitchen decor.\r\nMulti-Door Design: Features multiple doors for the refrigerator and freezer compartments, enhancing organization and convenience.\r\nTwin Cooling Plus: Utilizes dual evaporators and independent cooling systems for the refrigerator and freezer, maintaining optimal humidity levels and preventing odor transfer between compartments.\r\nAdjustable Shelving: Offers flexibility in organizing and storing items with adjustable shelves and door bins to accommodate groceries of various sizes.\r\nDigital Inverter Compressor: Provides efficient and quiet cooling performance while reducing energy consumption and minimizing noise levels.\r\nLED Lighting: Bright and energy-efficient LED lighting illuminates the interior of the refrigerator for better visibility and energy savings.\r\nFrost-Free Operation: Eliminates the need for manual defrosting by preventing ice buildup in the freezer compartment.\r\nWater and Ice Dispenser: Some models may feature a built-in water and ice dispenser for convenient access to filtered water and ice cubes', 300000.00, 0.00, 17, 'in_stock', 1, '2025-04-20 23:38:02', '2025-04-21 13:55:02', 'uploads/6805855ad9cfb.png', 'uploads/6805855ad9f77.png', 'uploads/6805855ada2c0.png'),
(3, 5, 'MIKA BLENDER 1.5L, 350W(MBLR101/WG)', '350W\r\n2 IN 1\r\n1.5L Unbreakable Blender Jug\r\nGrinder Jar\r\n4 Speed\r\nPulse Function\r\nSafety Lock Switch\r\nWhite & Grey', 5000.00, 5.00, 9, 'in_stock', 0, '2025-04-21 07:21:41', '2025-04-21 14:58:23', 'uploads/6805f2052f53d.jpg', 'uploads/6805f20530294.png', 'uploads/6805f20531aa9.jpg'),
(4, 5, 'Mika MTS2101 TOASTER 2 slice 700W Cancel Function-Cream white & Grey', '2 slice\r\n700W\r\nBrowning Control/7 Setting\r\nCancel Function\r\nRemovable Crumb Tray\r\nCool Touch\r\nCream white & Grey', 2500.00, 10.00, 20, 'in_stock', 0, '2025-04-21 10:44:39', '2025-04-21 14:53:35', 'uploads/680621979f7dc.jpg', 'uploads/680621979fdec.jpg', 'uploads/68062197a0325.jpg'),
(5, 6, 'Ramtons Wet And Dry Vacuum Cleaner- RM/553', 'Wet and Dry\r\n1400 Watts motor\r\nStainless steel 21 Liter tank\r\nCaster Wheeled\r\n5M Power cord\r\nBlower port design\r\nTop carry handle for portability\r\nSwiveling casters provide ease of movement in any direction\r\nAccessories: 1.5MX32MM hose telescopic metal tube\r\nFloor brush\r\nWater brush upholstery nozzle\r\n2 in 1 dusting brush\r\ncloth bag\r\nHepa filter, foam filter.', 14000.00, 10.00, 20, 'in_stock', 0, '2025-04-21 18:59:05', '2025-04-21 18:59:05', 'uploads/680695799b91d.webp', 'uploads/680695799e2b5.webp', 'uploads/680695799f01d.webp'),
(7, 6, 'Hisense Dishwasher', '15 place setting capacity for handling larger loads of dishes.\r\nFree-standing design allows for flexible placement in your kitchen.\r\nStylish silver finish adds an elegant touch to your kitchen décor.\r\nUser-friendly control panel for easy operation and program selection.', 69999.00, 15.00, 7, 'in_stock', 0, '2025-04-21 19:07:54', '2025-04-21 19:07:54', 'uploads/6806978ad66fb.png', 'uploads/6806978ad6a3e.png', 'uploads/6806978ad6ea6.png'),
(8, 4, 'MIKA Air Conditioner', 'Smart Inverter Technology – Energy Saving\r\nWifi – Mobile phone operation, Anywhere, Anytime!!!\r\nGEN Mode allows Air conditioner to operate under fluctuating voltage & on a smaller generator\r\nAntibacterial Filter – The High Density & Micro protection filter removes dust & allergen hence supply clean air.\r\nVitamin C Filter for clean air & healthy skin', 64000.00, 10.00, 18, 'in_stock', 0, '2025-04-21 19:13:19', '2025-04-21 19:13:19', 'uploads/680698cf787b7.webp', 'uploads/680698cf78af0.webp', NULL),
(9, 7, 'JBL Live 770NC Noise Cancelling Headphones', 'Adaptive Noise Cancellation Technology\r\nUp to 65 Hours of Battery Life\r\nBuilt-In Mic for Hands-Free Calls\r\nAmbient Aware & TalkThru\r\n10-Minute Charge for 4 Hours of Audio\r\nAudio Jack & Cable for Wired Use\r\nAutomatic Play & Pause\r\nMulti-Point Connection Support\r\nSupports Google Fast Pair', 24000.00, 30.00, 5, 'in_stock', 0, '2025-04-21 19:25:30', '2025-04-21 19:25:30', 'uploads/68069baa5384b.avif', 'uploads/68069baa53beb.avif', 'uploads/68069baa57de0.avif'),
(10, 7, 'ORAIMO OEP-E21 Wired Headphones Earphones', 'Superior Oraimo sound, with bass\r\nCable Length: 1.2m\r\nPlug Type: 3.5mm\r\nWorks with Android and iOS devices', 500.00, 0.00, 20, 'in_stock', 0, '2025-04-21 19:27:05', '2025-04-21 19:27:05', 'uploads/68069c0969f08.webp', NULL, NULL),
(11, 10, 'Samsung Galaxy A55', 'RAM: 8GB\r\nInternal Storage: 128GB, 256GB\r\nBattery: 5000mAh\r\nMain camera: 50MP+ 12MP+ 5MP\r\nFront camera: 32MP\r\nDisplay: 6.6 inches\r\nProcessor: Exynos 1480\r\nConnectivity: Dual sim, 4G, 5G, Wi-Fi\r\nColors: Ice Blue, Lilac, Navy, Lemon\r\nOS: Android 14, One UI 6.1', 45000.00, 0.00, 20, 'in_stock', 0, '2025-04-21 19:30:12', '2025-04-21 19:30:12', 'uploads/68069cc491645.jpg', 'uploads/68069cc4920b5.jpg', 'uploads/68069cc49248a.jpg'),
(12, 8, 'Dell Latitude 5480', 'Processor: Intel Core i5 6th Gen.\r\nScreen Size: 14 in\r\nRAM Size: 8 GB\r\nSSD Capacity: 256 GB\r\nProcessor Speed: 2.3GHz', 27000.00, 4.00, 5, 'in_stock', 0, '2025-04-21 19:32:35', '2025-04-21 19:32:35', 'uploads/68069d5311b19.jpg', 'uploads/68069d5311ea1.jpg', 'uploads/68069d5317bb2.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `order_id`, `product_id`, `user_id`, `rating`, `comment`, `review_date`) VALUES
(1, 1, 3, 2, 5, 'Product is as advertised', '2025-04-21 14:24:22');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_detail`
--

CREATE TABLE `shipping_detail` (
  `shipping_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipping_method` varchar(50) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT NULL,
  `estimated_delivery_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipping_detail`
--

INSERT INTO `shipping_detail` (`shipping_id`, `order_id`, `recipient_name`, `address`, `city`, `country`, `phone`, `tracking_number`, `shipping_method`, `shipping_cost`, `estimated_delivery_date`) VALUES
(9, NULL, 'Angela Wanjiru', 'Lumumba Drive, Roysambu', 'Nairobi', 'Kenya', '0756789022', NULL, 'standard', 5.00, '2025-04-26'),
(10, NULL, 'Angela Wanjiru', 'Lumumba Drive, Roysambu', 'Nairobi', 'Kenya', '0756789022', NULL, 'standard', 5.00, '2025-04-26'),
(11, NULL, 'Angela Wanjiru', 'Lumumba Drive, Roysambu', 'Nairobi', 'Kenya', '0756789022', NULL, 'standard', 5.00, '2025-04-26'),
(12, NULL, 'Angela Wanjiru', 'Lumumba Drive', 'Nairobi', 'Kenya', '0756789022', NULL, 'expedited', 15.00, '2025-04-23'),
(13, NULL, 'Angela Wanjiru', 'Lumumba Drive, Roysambu', 'Nairobi', 'Kenya', '0756789022', NULL, 'expedited', 15.00, '2025-04-23'),
(14, NULL, 'Angela Wanjiru', 'Lumumba Drive, Roysambu', 'Nairobi', 'Kenya', '0756789022', NULL, 'standard', 5.00, '2025-04-26');

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `subcategory_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`subcategory_id`, `category_id`, `name`, `description`, `image`, `created_at`) VALUES
(4, 1, 'Climate Control Appliances', 'Devices that regulate indoor temperature and humidity for comfort eg.  air conditioners, heaters, and fans', '/uploads/subcategories/68057f363aa5a.jpg', '2025-04-20 23:11:50'),
(5, 1, 'Kitchen Appliances', 'Devices used in the kitchen. e.g refrigerators, microwaves, blenders', '/uploads/subcategories/68057f5af2102.jpg', '2025-04-20 23:12:26'),
(6, 1, 'Cleaning Appliances', 'Devices used to clean household items e.g. washing machines, vacuum cleaners, dishwashers', '/uploads/subcategories/68057f9500d31.jpg', '2025-04-20 23:12:41'),
(7, 2, 'Audio Devices', 'Enhance sound experiences with headphones, speakers, and microphones.', '/uploads/subcategories/680699c144e94.jpg', '2025-04-21 19:17:21'),
(8, 2, 'Computing Devices', 'Power productivity and connectivity through laptops, desktops, and tablets.', '/uploads/subcategories/68069a21224ff.jpg', '2025-04-21 19:18:57'),
(9, 2, 'Video Devices', 'Deliver visual entertainment and communication via TVs, projectors, and cameras.', '/uploads/subcategories/68069a748030d.jpg', '2025-04-21 19:20:20'),
(10, 2, 'Communication Devices', 'Enable seamless interaction through smartphones, VoIP phones, and walkie-talkies.', '/uploads/subcategories/68069a9fbdbac.jpg', '2025-04-21 19:21:03'),
(11, 2, 'Wearable devices', 'Combine technology and convenience with smartwatches, fitness trackers, and AR glasses.', '/uploads/subcategories/68069ade2eb1f.jpg', '2025-04-21 19:22:06'),
(12, 3, 'Storage Accessories', 'Memory cards, external hard drives, USB flash drives', '/uploads/subcategories/68069eccc42b5.jpg', '2025-04-21 19:38:52'),
(13, 3, 'Charging Accessories', 'Cables (USB-C, Lightning), adapters, power banks, wireless chargers.', '/uploads/subcategories/68069f201a13c.jpg', '2025-04-21 19:40:16'),
(14, 3, 'Input Devices', 'Keyboards, mice, trackpads.', '/uploads/subcategories/68069f633ebeb.jpg', '2025-04-21 19:41:23'),
(15, 3, 'Networking Accessories', 'Ethernet cables, Wi-Fi routers, and network switches.', '/uploads/subcategories/68069fe2790a7.jpg', '2025-04-21 19:43:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `role`, `created_at`, `updated_at`, `is_admin`) VALUES
(1, 'Lydiah Kiboi', 'njambilydiah143@gmail.com', '$2y$10$DEFioN5DYg.a4bQi5LvjkOhydMHsBAas0lL.eu6IGaI0NhTVrvqIi', NULL, NULL, NULL, NULL, 'customer', '2025-04-20 21:51:52', '2025-04-20 21:51:52', 0),
(2, 'Angela Wanjiru', 'angelawk12@gmail.com', '$2y$10$u9UMfiH0KGWDM/rpMwMjB.WjV1jGMO24lOcQI7RZM3evxeQRI6rHa', NULL, NULL, NULL, NULL, 'customer', '2025-04-21 06:55:02', '2025-04-21 20:13:48', 0),
(3, 'Wattz Electronics', 'wattzelectronics@gmail.com', 'Admin143*#', 'Wattz', 'Electronics', '0769769077', NULL, 'admin', '2025-03-19 20:04:11', '2025-04-21 20:06:50', 1),
(4, 'Admin', 'admin@gmail.com', '$2y$10$K6zMBeOm4JnViU6c2/s3oOe6xFMFSH6nm55iJtJw.8TIwNVPw143u', NULL, NULL, NULL, NULL, 'admin', '2025-04-21 20:08:01', '2025-04-21 20:13:56', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_order`
--

CREATE TABLE `user_order` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `shipping_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_order`
--

INSERT INTO `user_order` (`order_id`, `user_id`, `shipping_id`, `total_amount`, `status`, `payment_method`, `payment_status`, `order_date`) VALUES
(1, 2, 9, 4750.00, 'delivered', 'cash_on_delivery', 'completed', '2025-03-17 10:16:12'),
(2, 2, 10, 2250.00, 'delivered', 'cash_on_delivery', 'completed', '2025-03-21 10:45:25'),
(3, 2, 11, 300000.00, 'cancelled', 'credit_card', '', '2025-04-21 13:54:00'),
(4, 2, 12, 300000.00, 'pending', 'credit_card', 'pending', '2025-04-21 13:56:43'),
(5, 2, 13, 2250.00, 'processing', 'cash_on_delivery', 'pending', '2025-04-21 14:53:35'),
(6, 2, 14, 4750.00, 'pending', 'cash_on_delivery', 'pending', '2025-04-21 14:58:23'),
(7, 2, 12, 300000.00, 'pending', 'credit_card', 'pending', '2025-04-12 13:56:43');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `user_id`, `product_id`, `added_at`) VALUES
(3, 2, 1, '2025-04-21 09:11:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_order_user` (`user_id`),
  ADD KEY `fk_product` (`product_id`),
  ADD KEY `fk_shipping` (`shipping_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_product_subcategory` (`subcategory_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `shipping_detail`
--
ALTER TABLE `shipping_detail`
  ADD PRIMARY KEY (`shipping_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`subcategory_id`),
  ADD KEY `idx_subcategory_category` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_order`
--
ALTER TABLE `user_order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `shipping_id` (`shipping_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shipping_detail`
--
ALTER TABLE `shipping_detail`
  MODIFY `shipping_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `subcategory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_order`
--
ALTER TABLE `user_order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_shipping` FOREIGN KEY (`shipping_id`) REFERENCES `shipping_detail` (`shipping_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `user_order` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`subcategory_id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `user_order` (`order_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `shipping_detail`
--
ALTER TABLE `shipping_detail`
  ADD CONSTRAINT `shipping_detail_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_order`
--
ALTER TABLE `user_order`
  ADD CONSTRAINT `user_order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_order_ibfk_2` FOREIGN KEY (`shipping_id`) REFERENCES `shipping_detail` (`shipping_id`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
