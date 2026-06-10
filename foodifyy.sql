-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2026 at 06:39 PM
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
-- Database: `foodifyy`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `created_recipes`
--

CREATE TABLE `created_recipes` (
  `cr_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `cooking_time` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `meal_type` enum('Breakfast','Lunch','Dinner','Dessert','Snack','Drinks') NOT NULL,
  `cuisine` enum('Melayu','Western','Asian') NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `category` enum('Vegetables','Fruits','Meat & Poultry','Seafood','Dairy','Dry Goods') NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `unit` varchar(50) DEFAULT 'kg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `name`, `description`, `price`, `stock`, `category`, `image`, `unit`, `created_at`) VALUES
(9, 'Apple (1kg)', 'Fresh red apples', 4.50, 34, 'Fruits', 'https://res.cloudinary.com/foodifyy/image/upload/v1778784057/apple_vxszkr.jpg', 'kg', '2026-05-14 18:14:36'),
(10, 'Mango (1kg)', 'Fresh sweet mangoes', 8.50, 25, 'Fruits', 'https://res.cloudinary.com/foodifyy/image/upload/v1778784376/mango_a25rv8.png', 'kg', '2026-05-14 18:14:36'),
(11, 'Chicken Breast (500g)', 'Fresh chicken breast', 12.90, 25, 'Meat & Poultry', 'https://res.cloudinary.com/foodifyy/image/upload/v1778783717/chickenbreast_rmhnin.jpg', 'kg', '2026-05-14 18:14:36'),
(12, 'Farm Fresh Milk (1L)', 'Fresh dairy milk', 6.50, 15, 'Dairy', 'https://res.cloudinary.com/foodifyy/image/upload/v1778783717/farmfreshmilk_j7ehnd.jpg', 'l', '2026-05-14 18:14:36'),
(13, 'Cap Rambutan Rice (5kg)', 'Premium white rice', 28.00, 20, 'Dry Goods', 'https://res.cloudinary.com/foodifyy/image/upload/v1778783717/berascap_rambutan_s8c9ar.jpg', 'kg', '2026-05-14 18:14:36'),
(14, 'Potato (1kg)', 'Fresh potatoes', 2.80, 24, 'Vegetables', 'https://res.cloudinary.com/foodifyy/image/upload/v1778783717/potato_erkdvo.jpg', 'kg', '2026-05-14 18:14:36'),
(15, 'Daun Kangkung (250g)', 'Daun kangkung fresh', 2.00, 25, 'Vegetables', 'https://res.cloudinary.com/foodifyy/image/upload/v1779766479/kangkung_zdysl9.png', 'g', '2026-05-26 03:32:46'),
(16, 'Terung (250g)', 'Fresh purple eggplants', 3.90, 33, 'Vegetables', 'https://res.cloudinary.com/foodifyy/image/upload/v1780571410/foodify/items/ydi9it6xii35pqhit0qa.jpg', 'g', '2026-05-28 02:04:56'),
(18, 'Grape (500g)', 'Fresh sweet grapes', 6.90, 50, 'Fruits', 'https://res.cloudinary.com/foodifyy/image/upload/v1781056153/foodify/items/l2mkpcltq1b2piaayzp6.jpg', 'g', '2026-06-10 01:16:59'),
(19, 'Banana (1kg)', 'Sweet ripe bananas', 2.50, 80, 'Fruits', 'https://res.cloudinary.com/foodifyy/image/upload/v1781055997/foodify/items/l464kcdideijrkcxpble.jpg', 'kg', '2026-06-10 01:16:59'),
(20, 'Orange (1kg)', 'Fresh juicy oranges', 5.50, 60, 'Fruits', 'https://res.cloudinary.com/foodifyy/image/upload/v1781055951/foodify/items/xyyiuybhqrhpnsrgkg1p.jpg', 'kg', '2026-06-10 01:16:59'),
(21, 'Green Apple (500g)', 'Crispy fresh green apples', 4.90, 45, 'Fruits', 'https://res.cloudinary.com/foodifyy/image/upload/v1781055920/foodify/items/qdemk6yjroyisudsevbq.jpg', 'g', '2026-06-10 01:16:59'),
(22, 'Dragon Fruit (1pc)', 'Fresh red dragon fruit', 4.50, 40, 'Fruits', 'https://res.cloudinary.com/foodifyy/image/upload/v1781055815/foodify/items/dibxphnejvfna64eifxk.jpg', 'pc', '2026-06-10 01:16:59'),
(23, 'Watermelon (1pc)', 'Seedless sweet watermelon', 8.90, 30, 'Fruits', 'https://res.cloudinary.com/foodifyy/image/upload/v1781055781/foodify/items/fkwjl881sh61ooizrdts.jpg', 'pc', '2026-06-10 01:16:59'),
(24, 'Cucumber (500g)', 'Fresh ripe red tomatoes', 3.20, 60, 'Vegetables', 'https://res.cloudinary.com/foodifyy/image/upload/v1781058429/foodify/items/g9pvzmpiqyumhppcxu4i.jpg', 'g', '2026-06-10 01:20:19'),
(25, 'Carrot (1kg)', 'Fresh crunchy carrots', 3.50, 55, 'Vegetables', 'https://res.cloudinary.com/foodifyy/image/upload/v1781055586/foodify/items/ycpcb2py2cffqek5wuk1.jpg', 'kg', '2026-06-10 01:20:19'),
(26, 'Chili (250g)', 'Fresh red chili', 2.80, 70, 'Vegetables', 'https://res.cloudinary.com/foodifyy/image/upload/v1781055440/foodify/items/fpusx7qe2hgkdend3b8c.jpg', 'g', '2026-06-10 01:20:19'),
(27, 'Corn (1pc)', 'Fresh sweet corn', 1.50, 90, 'Vegetables', 'https://res.cloudinary.com/foodifyy/image/upload/v1781055322/foodify/items/wwqu7b6lydbju2xpzzfr.jpg', 'pc', '2026-06-10 01:20:19'),
(28, 'Red Onion (500g)', 'Fresh red onion', 2.50, 65, 'Vegetables', 'https://res.cloudinary.com/foodifyy/image/upload/v1781055275/foodify/items/bnprxipmxjjsgv9cl5zz.jpg', 'g', '2026-06-10 01:20:19'),
(29, 'Whole Chicken (1kg)', 'Fresh whole chicken', 11.00, 40, 'Meat & Poultry', 'https://res.cloudinary.com/foodifyy/image/upload/v1781056904/foodify/items/rhfyybsgnxsguxvyywvx.jpg', 'kg', '2026-06-10 01:26:18'),
(30, 'Chicken Thigh (500g)', 'Fresh boneless chicken thigh', 8.50, 45, 'Meat & Poultry', 'https://res.cloudinary.com/foodifyy/image/upload/v1781056849/foodify/items/zqhtixtdvv98sunyqfnh.jpg', 'g', '2026-06-10 01:26:18'),
(31, 'Chicken Wings (1kg)', 'Fresh chicken wings', 10.50, 55, 'Meat & Poultry', 'https://res.cloudinary.com/foodifyy/image/upload/v1781056834/foodify/items/fapzijqa6zeo5rahocsw.jpg', 'kg', '2026-06-10 01:26:18'),
(32, 'Minced Beef (500g)', 'Fresh minced beef', 14.50, 35, 'Meat & Poultry', 'https://res.cloudinary.com/foodifyy/image/upload/v1781056717/foodify/items/ciitrikwkny3jndczvyy.jpg', 'g', '2026-06-10 01:26:18'),
(33, 'Beef Slice (300g)', 'Thinly sliced fresh beef', 16.90, 30, 'Meat & Poultry', 'https://res.cloudinary.com/foodifyy/image/upload/v1781056673/foodify/items/qvlupcwmzdz44e0rqq4w.jpg', 'g', '2026-06-10 01:26:18'),
(34, 'Beef Ribs (500g)', 'Fresh beef ribs', 18.90, 25, 'Meat & Poultry', 'https://res.cloudinary.com/foodifyy/image/upload/v1781056420/foodify/items/ajaaszrbi03snfcrou2r.jpg', 'g', '2026-06-10 01:26:18'),
(35, 'Beef Brisket (500g)', 'Fresh beef brisket', 17.90, 29, 'Meat & Poultry', 'https://res.cloudinary.com/foodifyy/image/upload/v1781056915/foodify/items/scav5bgclpr1xkiii123.jpg', 'g', '2026-06-10 01:26:48'),
(36, 'Full Cream Milk (1L)', 'Fresh full cream milk', 4.20, 60, 'Dairy', 'https://res.cloudinary.com/foodifyy/image/upload/v1781057757/foodify/items/qxcdjvfbixqnbgorccpq.jpg', 'L', '2026-06-10 01:29:56'),
(37, 'Chocolate Milk (1L)', 'Creamy chocolate flavoured milk', 5.50, 50, 'Dairy', 'https://res.cloudinary.com/foodifyy/image/upload/v1781057703/foodify/items/lpt9vyo4ynpugqikwa2v.jpg', 'L', '2026-06-10 01:29:56'),
(38, 'Yogurt (200g)', 'Fresh plain yogurt', 3.90, 45, 'Dairy', 'https://res.cloudinary.com/foodifyy/image/upload/v1781057691/foodify/items/kgr11ml5hule6t4tpqwz.jpg', 'g', '2026-06-10 01:29:56'),
(39, 'Cheddar Cheese (200g)', 'Sliced cheddar cheese', 7.90, 34, 'Dairy', 'https://res.cloudinary.com/foodifyy/image/upload/v1781057141/foodify/items/slkcszzsy4gracb21rgg.jpg', 'g', '2026-06-10 01:29:56'),
(40, 'Mozzarella Cheese (200g)', 'Fresh mozzarella cheese', 9.50, 30, 'Dairy', 'https://res.cloudinary.com/foodifyy/image/upload/v1781057109/foodify/items/q0tksvdpij1ryvalairh.jpg', 'g', '2026-06-10 01:29:56'),
(41, 'Butter (250g)', 'Salted butter block', 6.50, 40, 'Dairy', 'https://res.cloudinary.com/foodifyy/image/upload/v1781057082/foodify/items/wlrts2lmzpnlvd9opr1u.jpg', 'g', '2026-06-10 01:29:56'),
(42, 'Whipping Cream (250ml)', 'Fresh whipping cream', 8.90, 35, 'Dairy', 'https://res.cloudinary.com/foodifyy/image/upload/v1781057031/foodify/items/ieqfkomxrtqcvlri6uqo.jpg', 'ml', '2026-06-10 01:29:56'),
(43, 'Macaroni Pasta (500g)', 'Premium Macaroni pasta', 4.50, 46, 'Dry Goods', 'https://res.cloudinary.com/foodifyy/image/upload/v1781058324/foodify/items/ziztfygjwunb68rjh0sv.jpg', 'g', '2026-06-10 01:33:04'),
(44, 'Spaghetti (500g)', 'Premium spaghetti pasta', 4.50, 45, 'Dry Goods', 'https://res.cloudinary.com/foodifyy/image/upload/v1781058335/foodify/items/b7urqef108useqjf7bao.jpg', 'g', '2026-06-10 01:33:04'),
(45, 'Sugar (1kg)', 'Fine white granulated sugar', 3.20, 80, 'Dry Goods', 'https://res.cloudinary.com/foodifyy/image/upload/v1781058177/foodify/items/fhjoo9zxtzwf7p9caay6.jpg', 'kg', '2026-06-10 01:33:04'),
(46, 'Salt (500g)', 'Refined iodized salt', 1.50, 90, 'Dry Goods', 'https://res.cloudinary.com/foodifyy/image/upload/v1781058165/foodify/items/gyedo36xjics3bubnncg.jpg', 'g', '2026-06-10 01:33:04'),
(47, 'Saji Cooking Oil (2L)', 'Pure sunflower cooking oil', 12.50, 60, 'Dry Goods', 'https://res.cloudinary.com/foodifyy/image/upload/v1781058154/foodify/items/kitwlueg2htemvqekmc0.jpg', 'L', '2026-06-10 01:33:04'),
(48, 'Olive Oil (500ml)', 'Extra virgin olive oil', 18.90, 35, 'Dry Goods', 'https://res.cloudinary.com/foodifyy/image/upload/v1781058137/foodify/items/cwk5v2klfzmwergozo57.jpg', 'ml', '2026-06-10 01:33:04'),
(49, 'Oyster Sauce (255g)', 'Premium oyster sauce', 4.90, 50, 'Dry Goods', 'https://res.cloudinary.com/foodifyy/image/upload/v1781058069/foodify/items/byryk8suk4uiczgidoac.jpg', 'g', '2026-06-10 01:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 3.50,
  `status` enum('pending','processing','completed') DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `shipping_name` varchar(100) DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(23, 'amrllsfyp@gmail.com', '38eab59fbd825122f89f9440f85c8042b060d350cc78808ad305b06b92139df7', '2026-06-07 08:43:33', '2026-06-07 06:28:33'),
(24, 'amrlls2606@gmail.com', '81d25cb74c71b9bd4a164e8786a27c0acf90ff60f5c45115b1d938c7164d462b', '2026-06-09 03:32:09', '2026-06-09 01:17:09');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('online_banking','cod') NOT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `recipe_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `ingredients` text NOT NULL,
  `instructions` text NOT NULL,
  `cooking_time` varchar(50) DEFAULT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner','Dessert','Snack','Drinks') NOT NULL,
  `cuisine` enum('Melayu','Western','Asian') NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`recipe_id`, `title`, `description`, `ingredients`, `instructions`, `cooking_time`, `meal_type`, `cuisine`, `image`, `video_url`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'Nasi Goreng Kampung', 'Hidangan nasi goreng tradisional Malaysia yang popular dengan rasa pedas cili padi dan aroma ikan bilis goreng yang garing.', '3 cawan nasi putih (sejuk)\n5 biji cili padi (tumbuk)\n3 ulas bawang merah (tumbuk)\n2 ulas bawang putih (tumbuk)\n1/2 cawan ikan bilis (goreng garing)\n1 ikat sayur kangkung\n2 biji telur\n1 sudu besar sos tiram\nGaram secukup rasa', 'Panaskan minyak dan tumis bahan tumbuk sehingga pecah minyak dan naik bau.\nMasukkan telur dan kacau hancur sehingga masak.\nMasukkan nasi putih dan sayur kangkung. Gaul rata dengan api besar.\nPerasakan dengan sos tiram dan garam secukupnya.\nAkhali sekali, masukkan ikan bilis yang telah digoreng garing tadi. Gaul sebentar dan tutup api.\nSedia untuk dihidangkan bersama telur mata dan timun.', '15', 'Breakfast', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1778693831/nasigorengkampung_x8klto.png', 'https://res.cloudinary.com/foodifyy/video/upload/v1778697283/nasigorengkampungvid_s9bysw.mp4', 1, '2026-05-07 03:43:40', '2026-05-13 18:37:16'),
(3, 'Creamy Carbonara Pasta', 'Pasta berkrim asli gaya Itali menggunakan kuning telur dan keju parmesan tanpa krim putar.', '200g Spaghetti, 100g beef bacon, 2 biji telur, 50g keju Parmesan, lada hitam, garam.', '1. Rebus pasta sehingga al dente.\n2. Goreng beef bacon sehingga garing.\n3. Campurkan telur dan keju dalam mangkuk.\n4. Tutup api kuali bacon, masukkan pasta dan campuran telur. Gaul cepat supaya telur tak berketul.\n5. Hidang dengan lada hitam.', '20 ', 'Dinner', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1778566892/creamy_carbonara_uqyhbh.png ', 'https://res.cloudinary.com/foodifyy/video/upload/v1778590082/creamy_carbonara_v5imwx.mp4', 1, '2026-05-07 05:32:41', '2026-05-13 02:52:08'),
(5, 'Mango Sticky Rice', 'Pencuci mulut popular Thailand - Pulut lemak manis dimakan dengan mangga segar.', '1 cawan pulut, 1 cawan santan pekat, 3 sudu gula, secubit garam, sebiji mangga masak.', '1. Kukus pulut sehingga masak.\n2. Masak santan, gula dan garam atas api kecil sehingga larut.\n3. Tuangkan separuh santan ke dalam pulut dan biar meresap.\n4. Hidangkan pulut dengan mangga dan curah baki kuah santan.', '40', 'Dessert', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1778599670/pulut_mangga_buheji.png', 'https://res.cloudinary.com/foodifyy/video/upload/v1778599618/pulut_mangga_ul6xnh.mp4', 1, '2026-05-07 05:32:41', '2026-05-13 02:55:59'),
(7, 'Nasi Lemak', 'Traditional Malaysian coconut rice served with sambal and side dishes.', '1 plate coconut rice\r\n2 tbsp sambal\r\n1 boiled egg\r\nFried anchovies\r\nCucumber slices', '1. Prepare a serving plate and place the coconut rice in the center.\r\n2. Add sambal on the side of the rice.\r\n3. Slice the boiled egg into halves and place beside the rice.\r\n4. Add fried anchovies and cucumber slices.\r\n5. Serve warm for breakfast.', '20', 'Breakfast', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1778694306/nasilemak_c8bdx4.png', 'https://res.cloudinary.com/foodifyy/video/upload/v1778697008/nasilemakvideo_olw3h2.mp4', 1, '2026-05-13 13:27:59', '2026-06-09 14:21:21'),
(9, 'Grilled Chicken Chop', 'Juicy grilled chicken served with fries and fresh coleslaw.', '1 chicken chop\r\nFrench fries\r\nMixed salad\r\nBlack pepper sauce\r\nSalt\r\nOlive oil', '1. Season the chicken with salt and olive oil.\r\n2. Heat a grill pan over medium heat.\r\n3. Grill the chicken for 6-7 minutes on each side until fully cooked.\r\n4. Prepare the french fries according to package instructions.\r\n5. Arrange the salad on a serving plate.\r\n6. Place the grilled chicken beside the fries and salad.\r\n7. Pour black pepper sauce over the chicken before serving.', '30', 'Lunch', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1778693829/grilledchickenchop_tgp9wp.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1778699659/grilledchickenchop_q8kggm.mp4', 1, '2026-05-13 13:27:59', '2026-06-04 07:26:50'),
(10, 'Seafood Tomyam', 'Spicy and sour Thai soup filled with fresh seafood.', '2 tbsp tomyam paste\n100g shrimp\n100g squid\nMushrooms\n2 stalks lemongrass\n3 cups water', '1. Boil water in a medium pot.\r\n2. Add lemongrass and tomyam paste into the pot.\r\n3. Stir until the paste dissolves completely.\r\n4. Add mushrooms and cook for 2 minutes.\r\n5. Add shrimp and squid into the soup.\r\n6. Simmer until the seafood is fully cooked.\r\n7. Serve hot while the soup is still steaming.', '25', 'Dinner', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1778693830/tomyamseafood_sj4swz.png', 'https://res.cloudinary.com/foodifyy/video/upload/v1778695509/tomyumvid_nkyyfu.mp4', 1, '2026-05-13 13:27:59', '2026-05-13 18:05:30'),
(13, 'Caramel Pudding', 'Smooth and creamy caramel pudding dessert.', '2 cups milk\r\n2 eggs\r\n1/2 cup sugar\r\n1 tsp vanilla essence', '1. Heat sugar in a pan until it melts into caramel.\r\n2. Pour the caramel into pudding cups.\r\n3. In a bowl, whisk eggs, milk, and vanilla essence together.\r\n4. Strain the mixture for a smoother texture.\r\n5. Pour the mixture into the pudding cups.\r\n6. Steam for 20-25 minutes until set.\r\n7. Chill before serving.', '35', 'Dessert', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1778693831/caramelpudding_neowgt.png', 'https://res.cloudinary.com/foodifyy/video/upload/v1778695830/pudingcaramelvid_djwkml.mp4', 1, '2026-05-13 13:27:59', '2026-05-13 18:11:06'),
(15, 'Sausage Roll', 'Simple sausage roll snack with soft bread.', '2 sausages\r\n2 slices bread\r\nMayonnaise\r\nChili sauce', '1. Cook the sausages until heated through.\r\n2. Flatten the bread slices lightly with a rolling pin.\r\n3. Place a sausage on each bread slice.\r\n4. Add mayonnaise and chili sauce.\r\n5. Roll the bread tightly around the sausage.\r\n6. Serve immediately or toast lightly before serving.', '15', 'Snack', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1778693831/rolledsausagee_wnohwm.jpg', 'videos/snack_sausageroll.mp4', 1, '2026-05-13 13:27:59', '2026-05-13 17:50:26'),
(16, 'Iced Lemon Tea', 'Refreshing cold lemon tea served with ice.', '1 tea bag\r\n1 lemon\r\nIce cubes\r\n2 tsp sugar\r\n1 cup water', '1. Brew the tea bag in hot water for 3 minutes.\r\n2. Remove the tea bag and add sugar.\r\n3. Let the tea cool to room temperature.\r\n4. Fill a glass with ice cubes.\r\n5. Pour the tea into the glass.\r\n6. Add fresh lemon slices before serving.', '5', 'Drinks', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1778693831/lemonIcedTea_nvx0fu.jpg', 'videos/drink_lemontea.mp4', 1, '2026-05-13 13:27:59', '2026-05-13 17:40:18'),
(18, 'Kuey Teow Goreng', 'Delicious stir-fried flat rice noodles cooked with seafood, vegetables, and savory sauce.', '200g flat rice noodles\r\n100g shrimp\r\n1 egg\r\n2 cloves garlic\r\n1 cup bean sprouts\r\n1 tbsp chili paste\r\n2 tbsp soy sauce\r\n1 tbsp oyster sauce\r\nCooking oil\r\nSpring onion', '1. Heat cooking oil in a wok over medium heat.\r\n2. Mince the garlic and stir-fry until fragrant.\r\n3. Add shrimp and cook until it turns pink.\r\n4. Push the shrimp aside and crack the egg into the wok.\r\n5. Scramble the egg lightly before mixing with the shrimp.\r\n6. Add chili paste and stir well for extra flavor.\r\n7. Put the flat rice noodles into the wok.\r\n8. Add soy sauce and oyster sauce evenly over the noodles.\r\n9. Stir-fry everything together for 3-4 minutes.\r\n10. Add bean sprouts and mix briefly to keep them crunchy.\r\n11. Garnish with chopped spring onion before serving.\r\n12. Serve hot immediately.', '20', 'Breakfast', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780556394/foodify/admin_recipes/twp66yypucdk27kdseyx.png', 'https://res.cloudinary.com/foodifyy/video/upload/v1778695168/kueyteowvid_xqrxho.mp4', 1, '2026-05-13 13:36:30', '2026-06-09 03:10:05'),
(28, 'Ayam Masak Merah', 'A classic Malaysian red chicken dish cooked in spicy tomato sauce. Rich, thick and full of flavour.', '1 whole chicken, cut into pieces\r\n4 tablespoons cooking oil\r\n1 can tomato puree\r\n3 tablespoons chili paste\r\n1 onion, sliced\r\n3 cloves garlic, minced\r\n1 inch ginger, minced\r\n2 lemongrass stalks, bruised\r\n1 teaspoon sugar\r\nSalt to taste\r\n1 cup water', 'Heat oil in a pan over medium heat.\r\nFry onion, garlic and ginger until soft and fragrant.\r\nAdd chili paste and lemongrass. Stir for 2 minutes.\r\nAdd chicken pieces. Cook until chicken changes colour.\r\nPour in tomato puree and water. Stir well.\r\nAdd sugar and salt. Mix everything together.\r\nCover and cook on low heat for 20 minutes.\r\nStir occasionally until sauce thickens.\r\nTaste and adjust salt if needed.\r\nServe hot with white rice.', '35', 'Dinner', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780979328/foodify/admin_recipes/crzaenmj71l0xiwmdk3a.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781015325/foodify/recipe_video/zc3pmzbcvs9csky2vw7o.mp4', 1, '2026-06-09 02:04:17', '2026-06-09 14:29:01'),
(29, 'Mee Goreng', 'A popular Malaysian fried noodle dish with a spicy and savoury sauce. Best enjoyed hot straight from the wok.', '300g yellow noodles\r\n2 tablespoons cooking oil\r\n2 eggs\r\n100g bean sprouts\r\n3 tablespoons chili paste\r\n2 tablespoons soy sauce\r\n1 tablespoon tomato sauce\r\n1 tablespoon sweet sauce\r\n3 cloves garlic, minced\r\n1 onion, sliced\r\n100g tofu, cubed\r\n1 potato, boiled and cubed\r\nSalt to taste\r\n1 lime, cut into wedges', 'Heat oil in a wok over high heat.\r\nFry garlic and onion until golden.\r\nAdd chili paste. Stir for 1 minute.\r\nAdd tofu and potato. Mix well.\r\nPush everything to the side. Crack eggs into the wok.\r\nScramble the eggs lightly then mix with other ingredients.\r\nAdd yellow noodles. Toss everything together.\r\nPour in soy sauce, tomato sauce and sweet sauce.\r\nAdd bean sprouts. Stir fry for 2 minutes.\r\nAdd salt to taste.\r\nServe hot with lime wedges on the side.', '20', 'Breakfast', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780979253/foodify/admin_recipes/nqo6owl7mxeo7f22unhc.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781012951/foodify/recipe_video/ycb7qbnofitr6m3wqg1d.mp4', 1, '2026-06-09 02:07:30', '2026-06-09 13:49:22'),
(30, 'Kuih Seri Muka', 'A traditional Malaysian two layer kuih with sticky glutinous rice at the bottom and smooth pandan custard on top.', '2 cups glutinous rice, soaked for 4 hours\r\n1 cup coconut milk\r\n1 teaspoon salt\r\n4 eggs\r\n1 cup sugar\r\n1 cup coconut milk\r\n3 tablespoons corn flour\r\n1 tablespoon plain flour\r\n1 cup pandan juice\r\n1 drop green food colouring\r\nA pinch of salt', 'Drain soaked glutinous rice. Mix with coconut milk and salt.\r\nSteam the rice for 25 minutes until fully cooked.\r\nPress the rice firmly into a greased tray. Set aside.\r\nMix eggs and sugar in a bowl. Whisk until combined.\r\nAdd coconut milk, corn flour and plain flour. Mix well.\r\nPour in pandan juice and green colouring. Stir until smooth.\r\nCook the custard mixture over low heat. Stir constantly.\r\nRemove from heat when mixture thickens slightly.\r\nPour the custard on top of the pressed rice.\r\nSteam for 20 minutes until the custard layer is set.\r\nLet it cool completely before cutting into pieces.', '60', 'Dessert', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780981054/foodify/admin_recipes/o3sp6ntb5yydy7jpkxyk.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781013402/foodify/recipe_video/cxcnaskzxkjedwnbxlop.mp4', 1, '2026-06-09 02:09:52', '2026-06-09 13:56:51'),
(31, 'Onde Onde', 'Small green glutinous rice balls filled with melted palm sugar and rolled in fresh grated coconut.', '2 cups glutinous rice flour\r\n1 cup pandan juice\r\nA pinch of salt\r\n150g palm sugar, chopped small\r\n1 cup fresh grated coconut\r\nA pinch of salt for coconut', 'Mix grated coconut with a pinch of salt. Set aside.\r\nCombine glutinous rice flour and salt in a bowl.\r\nAdd pandan juice slowly. Mix until a soft dough forms.\r\nTake a small piece of dough. Flatten it in your palm.\r\nPlace a small piece of palm sugar in the centre.\r\nWrap the dough around the sugar. Roll into a ball.\r\nRepeat until all dough is used.\r\nBring a pot of water to boil.\r\nDrop the balls into boiling water.\r\nCook until the balls float to the surface.\r\nRemove and roll immediately in grated coconut.\r\nServe while still warm.', '40', 'Dessert', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780980550/foodify/admin_recipes/sortkrlr0vmphjbukgpw.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781013062/foodify/recipe_video/n32hngyyucmzri4spxoj.mp4', 1, '2026-06-09 02:09:52', '2026-06-09 13:51:15'),
(32, 'Pancake', 'Soft and fluffy pancakes stacked high and served with butter and maple syrup. A perfect sweet breakfast to start the day.', '2 cups plain flour\r\n2 tablespoons sugar\r\n2 teaspoons baking powder\r\n1 teaspoon salt\r\n2 eggs\r\n1 and half cups milk\r\n3 tablespoons melted butter\r\n1 teaspoon vanilla extract\r\nButter for cooking\r\nMaple syrup for serving', 'Mix flour, sugar, baking powder and salt in a large bowl.\r\nIn another bowl, whisk eggs, milk, melted butter and vanilla.\r\nPour the wet mixture into the dry mixture.\r\nStir gently until just combined. Do not overmix.\r\nHeat a pan over medium low heat.\r\nAdd a small piece of butter and let it melt.\r\nPour half a cup of batter into the pan.\r\nCook until bubbles appear on the surface.\r\nFlip the pancake and cook for 1 more minute.\r\nRepeat until all batter is used.\r\nStack the pancakes on a plate.\r\nTop with butter and pour maple syrup over.\r\nServe warm.', '25', 'Breakfast', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1780980232/foodify/admin_recipes/nnpracgaelav4swanxey.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781013828/foodify/recipe_video/o9ageoz6llspitazrfkb.mp4', 1, '2026-06-09 02:16:29', '2026-06-09 14:03:55'),
(33, 'Crispy Chicken Burger', 'A juicy and crispy fried chicken burger with fresh vegetables and creamy sauce. Perfect for a satisfying lunch.', '2 chicken breast fillets\r\n2 burger buns\r\n2 tablespoons plain flour\r\n2 tablespoons corn flour\r\n1 egg\r\nHalf cup milk\r\n1 teaspoon garlic powder\r\n1 teaspoon paprika\r\nSalt to taste\r\nBlack pepper to taste\r\nOil for frying\r\n2 lettuce leaves\r\n2 slices tomato\r\n2 slices cheddar cheese\r\n3 tablespoons mayonnaise\r\n1 tablespoon chili sauce', 'Flatten chicken fillets to even thickness.\r\nSeason with garlic powder, paprika, salt and black pepper.\r\nMix plain flour and corn flour in a bowl.\r\nIn another bowl, whisk egg and milk together.\r\nDip chicken into the egg mixture.\r\nCoat the chicken well with the flour mixture.\r\nHeat oil in a pan over medium heat.\r\nFry chicken for 5 minutes on each side until golden and crispy.\r\nRemove and place on a paper towel to drain excess oil.\r\nSlice burger buns in half and toast them lightly.\r\nMix mayonnaise and chili sauce together.\r\nSpread the sauce on both sides of the bun.\r\nPlace lettuce on the bottom bun.\r\nAdd the fried chicken on top.\r\nPlace cheese and tomato slices on the chicken.\r\nCover with the top bun and serve immediately.', '25', 'Lunch', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1781013686/foodify/admin_recipes/qp4zxfls7nc1if6itccf.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781013900/foodify/recipe_video/mxqh1oyxhyt4b72tml4j.mp4', 1, '2026-06-09 02:19:28', '2026-06-09 14:05:09'),
(34, 'Scrambled Eggs with Toast', 'Soft and creamy scrambled eggs served on top of crispy golden toast. A simple and filling breakfast that is quick to make.', '4 eggs\r\n2 tablespoons butter\r\n3 tablespoons milk\r\nSalt to taste\r\nBlack pepper to taste\r\n2 slices bread\r\nFresh chives for topping', 'Crack eggs into a bowl.\r\nAdd milk, salt and black pepper.\r\nWhisk everything together until well combined.\r\nToast the bread slices until golden and crispy. Set aside.\r\nHeat a pan over low heat.\r\nAdd butter and let it melt slowly.\r\nPour in the egg mixture.\r\nUsing a spatula, gently push the eggs from the edges to the centre.\r\nKeep stirring slowly on low heat.\r\nRemove from heat while eggs still look slightly wet.\r\nThe eggs will continue cooking from the heat of the pan.\r\nPlace the toast on a plate.\r\nSpoon the scrambled eggs on top of the toast.\r\nSprinkle with chives and black pepper.\r\nServe immediately.', '10', 'Breakfast', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1780982733/foodify/admin_recipes/gjeymowfqumxvbxxo1de.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781014447/foodify/recipe_video/wwzed8w47xpiznfctbrl.mp4', 1, '2026-06-09 02:19:29', '2026-06-09 14:14:12'),
(35, 'Fish and Chips', 'Crispy battered fish served with thick cut fried potatoes. A classic British comfort food that is simple and delicious.', '2 fish fillets\r\n3 large potatoes, cut into thick strips\r\n1 cup plain flour\r\n1 teaspoon baking powder\r\n1 cup cold water\r\n1 teaspoon garlic powder\r\n1 teaspoon paprika\r\nSalt to taste\r\nBlack pepper to taste\r\nOil for frying\r\nLemon wedges for serving\r\nTartar sauce for serving', 'Soak potato strips in cold water for 30 minutes. Drain and pat dry.\r\nHeat oil in a deep pan over medium high heat.\r\nFry potato strips until golden and crispy.\r\nRemove and drain on paper towel. Season with salt. Set aside.\r\nMix flour, baking powder, garlic powder, paprika, salt and pepper in a bowl.\r\nAdd cold water slowly. Whisk until smooth batter forms.\r\nPat fish fillets dry with paper towel.\r\nSeason fish with salt and black pepper.\r\nDip fish into the batter. Make sure it is fully coated.\r\nCarefully lower fish into hot oil.\r\nFry for 4 minutes on each side until golden and crispy.\r\nRemove and drain on paper towel.\r\nServe fish and chips together on a plate.\r\nAdd lemon wedges and tartar sauce on the side.', '45', 'Dinner', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1780983214/foodify/admin_recipes/ewebkfjx8baxqhhirmed.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781012884/foodify/recipe_video/suio7j1tuxzd6uwv4lgt.mp4', 1, '2026-06-09 02:21:52', '2026-06-09 13:48:14'),
(36, 'Chocolate Lava Cake', 'A warm chocolate cake with a soft and gooey melted chocolate centre. Best served with a scoop of vanilla ice cream.', '100g dark chocolate\r\n100g butter\r\n2 eggs\r\n2 egg yolks\r\n80g sugar\r\n2 tablespoons plain flour\r\nA pinch of salt\r\nButter for greasing\r\nCocoa powder for dusting\r\nVanilla ice cream for serving', 'Preheat oven to 200 degrees celsius.\r\nGrease ramekins with butter and dust with cocoa powder.\r\nMelt dark chocolate and butter together in a bowl over hot water.\r\nStir until smooth. Let it cool slightly.\r\nIn another bowl, whisk eggs, egg yolks and sugar until thick and pale.\r\nPour the chocolate mixture into the egg mixture.\r\nAdd flour and salt. Fold gently until just combined.\r\nPour the batter into the prepared ramekins.\r\nBake for 10 to 12 minutes.\r\nThe edges should be set but the centre should still be soft.\r\nRemove from oven and let it rest for 1 minute.\r\nRun a knife around the edges.\r\nPlace a plate on top of the ramekin and flip it over.\r\nServe immediately with vanilla ice cream.', '25', 'Dessert', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1780983053/foodify/admin_recipes/vyip4eeazojhrkvf3bf8.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781014473/foodify/recipe_video/hvhwrwhcodt4allbbdol.mp4', 1, '2026-06-09 02:21:52', '2026-06-09 14:14:39'),
(37, 'Tiramisu', 'A classic Italian dessert made with layers of coffee soaked biscuits and creamy mascarpone cheese. Rich, smooth and not too sweet.', '250g mascarpone cheese\r\n3 eggs, separated\r\n80g sugar\r\n1 cup strong black coffee, cooled\r\n2 tablespoons sugar for coffee\r\n200g ladyfinger biscuits\r\nCocoa powder for dusting\r\n1 teaspoon vanilla extract', 'Make strong black coffee. Add 2 tablespoons sugar and stir. Let it cool completely.\r\nSeparate egg yolks and egg whites into two bowls.\r\nWhisk egg yolks and sugar until thick and pale yellow.\r\nAdd mascarpone cheese and vanilla. Mix until smooth.\r\nIn another bowl, whisk egg whites until stiff peaks form.\r\nGently fold egg whites into the mascarpone mixture.\r\nDip each ladyfinger quickly into the coffee. Do not soak too long.\r\nArrange a layer of dipped ladyfingers in a dish.\r\nSpread half of the mascarpone mixture on top.\r\nAdd another layer of dipped ladyfingers.\r\nSpread the remaining mascarpone mixture on top.\r\nSmooth the surface with a spatula.\r\nCover and refrigerate for at least 4 hours or overnight.\r\nDust generously with cocoa powder before serving.', '30', 'Dessert', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1780984104/foodify/admin_recipes/faoxfct3au1adgjpnyy2.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781013212/foodify/recipe_video/osnmzk5xvbi4fjnj2rmy.mp4', 1, '2026-06-09 02:26:00', '2026-06-09 13:53:42'),
(38, 'Cheesy Loaded Nachos', 'Crispy tortilla chips loaded with melted cheese, jalapenos, sour cream and salsa. A fun and easy snack for sharing.', '200g tortilla chips\r\n2 cups shredded cheddar cheese\r\nHalf cup mozzarella cheese\r\n1 can black beans, drained\r\n1 tomato, diced\r\nHalf onion, diced\r\n2 jalapenos, sliced\r\nHalf cup sour cream\r\nHalf cup salsa sauce\r\n1 teaspoon paprika\r\nFresh coriander for topping', 'Preheat oven to 180 degrees celsius.\r\nSpread tortilla chips in a single layer on a baking tray.\r\nSprinkle black beans evenly over the chips.\r\nAdd diced tomato and onion on top.\r\nPlace jalapeno slices over everything.\r\nSprinkle cheddar cheese and mozzarella generously over the top.\r\nDust with paprika.\r\nBake for 10 to 15 minutes until cheese is fully melted and bubbly.\r\nRemove from oven carefully.\r\nAdd dollops of sour cream on top.\r\nDrizzle salsa sauce over everything.\r\nSprinkle fresh coriander on top.\r\nServe immediately while still hot.', '20', 'Snack', 'Western', 'https://res.cloudinary.com/foodifyy/image/upload/v1780984637/foodify/admin_recipes/a1wyah7qmkyvao2tnavj.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781012628/foodify/recipe_video/lhgi2mwv5kkryom0muxs.mp4', 1, '2026-06-09 02:26:00', '2026-06-09 13:43:58'),
(39, 'Tuna Onigiri', 'Japanese rice balls filled with creamy tuna mayo and wrapped in crispy nori seaweed. A simple and popular Japanese snack or breakfast.', '2 cups Japanese short grain rice\r\n2 and half cups water\r\n1 can tuna, drained\r\n3 tablespoons mayonnaise\r\n1 teaspoon soy sauce\r\nSalt to taste\r\n4 sheets nori seaweed, cut into strips\r\n1 teaspoon sesame seeds', 'Wash rice until water runs clear.\r\nCook rice with water in a rice cooker or pot.\r\nLet rice cool slightly until safe to handle.\r\nMix tuna, mayonnaise and soy sauce in a bowl. Set aside.\r\nWet your hands with water and sprinkle a little salt on your palms.\r\nTake a handful of rice and flatten it on your palm.\r\nPlace a spoonful of tuna mixture in the centre.\r\nCover the filling with more rice.\r\nShape the rice into a triangle by pressing firmly with both hands.\r\nMake sure the filling is completely sealed inside.\r\nWrap a strip of nori around the bottom of the onigiri.\r\nSprinkle sesame seeds on top.\r\nRepeat until all rice and filling is used.\r\nServe immediately or wrap in cling film to keep fresh.', '30', 'Breakfast', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1780985151/foodify/admin_recipes/ksdp8feri5qbxkg9gwog.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781011926/foodify/recipe_video/uf53ug2vfeiatv5vgfvq.mp4', 1, '2026-06-09 02:38:39', '2026-06-09 13:32:16'),
(40, 'Beef Dumplings', 'Juicy and flavourful beef dumplings with a thin wrapper. Pan fried until golden and crispy on the bottom. Great as a snack or starter.', '250g minced beef\r\n1 pack dumpling wrappers\r\n2 cloves garlic, minced\r\n1 teaspoon ginger, minced\r\n2 stalks spring onion, chopped\r\n1 tablespoon soy sauce\r\n1 tablespoon oyster sauce\r\n1 teaspoon sesame oil\r\n1 teaspoon sugar\r\nBlack pepper to taste\r\n2 tablespoons cooking oil\r\nHalf cup water for steaming\r\nSoy sauce and chili oil for dipping', 'Mix minced beef, garlic, ginger and spring onion in a bowl.\r\nAdd soy sauce, oyster sauce, sesame oil, sugar and black pepper.\r\nMix everything well until fully combined.\r\nPlace one dumpling wrapper on your palm.\r\nPut a teaspoon of filling in the centre.\r\nWet the edges of the wrapper with a little water.\r\nFold the wrapper in half over the filling.\r\nPress and pleat the edges to seal tightly.\r\nRepeat until all filling is used.\r\nHeat oil in a flat pan over medium heat.\r\nPlace dumplings flat side down in the pan.\r\nFry for 2 minutes until the bottom is golden brown.\r\nPour in half cup of water carefully.\r\nCover the pan immediately with a lid.\r\nSteam for 6 minutes until water evaporates completely.\r\nRemove lid and cook for 1 more minute until bottom is crispy again.\r\nServe hot with soy sauce and chili oil for dipping.', '40', 'Snack', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1780984169/foodify/admin_recipes/zgxkwhqbu8fjgthphp5e.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781012258/foodify/recipe_video/yanl15cubdd4ws4efk5b.mp4', 1, '2026-06-09 02:38:39', '2026-06-09 13:37:52'),
(41, 'Japanese Tamagoyaki', 'A sweet and savory Japanese rolled omelette made with layers of thin egg. Soft, fluffy and slightly sweet. A popular Japanese breakfast side dish.', '4 eggs\r\n2 tablespoons dashi stock\r\n1 tablespoon soy sauce\r\n1 tablespoon mirin\r\n1 teaspoon sugar\r\nA pinch of salt\r\n1 tablespoon cooking oil\r\nSpring onion for topping', 'Crack all eggs into a bowl.\r\nAdd dashi stock, soy sauce, mirin, sugar and salt.\r\nWhisk everything together until well combined.\r\nStrain the egg mixture through a sieve for a smoother texture.\r\nHeat a tamagoyaki pan or small rectangular pan over medium low heat.\r\nAdd a little oil and spread it evenly using a paper towel.\r\nPour a thin layer of egg mixture into the pan.\r\nTilt the pan to spread the egg evenly.\r\nWhen the egg is half cooked and still slightly wet on top.\r\nRoll the egg slowly from one end to the other.\r\nPush the rolled egg to one side of the pan.\r\nAdd oil again and pour another thin layer of egg mixture.\r\nLift the existing roll so the new egg flows underneath.\r\nRoll everything together again from the same side.\r\nRepeat until all egg mixture is used.\r\nRemove from pan and place on a bamboo mat.\r\nRoll tightly in the mat and shape into a rectangle.\r\nLet it rest for 2 minutes.\r\nSlice into pieces and serve with spring onion on top.', '15', 'Breakfast', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1780984779/foodify/admin_recipes/e0v82zvmae56qllgeqdh.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781011893/foodify/recipe_video/oxdo3bv2o4w1kraf9jos.mp4', 1, '2026-06-09 02:40:27', '2026-06-09 13:31:45'),
(42, 'Korean Tteokbokki', 'A popular Korean street food made with chewy rice cakes cooked in a spicy and sweet red sauce. Simple, addictive and very satisfying.', '300g Korean rice cakes\r\n2 cups water\r\n3 tablespoons gochujang paste\r\n1 tablespoon gochugaru flakes\r\n2 tablespoons soy sauce\r\n2 tablespoons sugar\r\n1 tablespoon corn syrup\r\n2 stalks spring onion, cut into pieces\r\n2 boiled eggs, peeled\r\n1 sheet fish cake, sliced\r\n1 teaspoon sesame oil\r\nSesame seeds for topping', 'Soak rice cakes in cold water for 10 minutes if they are hard. Drain and set aside.\r\nPour water into a wide pan over medium heat.\r\nAdd gochujang, gochugaru, soy sauce, sugar and corn syrup.\r\nStir everything together until sauce is smooth.\r\nBring the sauce to a boil.\r\nAdd rice cakes and fish cake slices into the pan.\r\nStir well to coat everything in the sauce.\r\nCook for 8 to 10 minutes until sauce thickens and rice cakes become soft.\r\nStir occasionally to prevent sticking.\r\nAdd spring onion and boiled eggs.\r\nCook for 2 more minutes.\r\nDrizzle sesame oil over the top.\r\nSprinkle sesame seeds before serving.\r\nServe hot straight from the pan.', '25', 'Lunch', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1780985285/foodify/admin_recipes/zbskauck4ncr5kfatlh5.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781011827/foodify/recipe_video/p9x3dllhpl7d3ofjtgin.mp4', 1, '2026-06-09 02:43:31', '2026-06-09 13:30:35'),
(43, 'Hainanese Chicken Rice', 'A classic Southeast Asian dish of tender poached chicken served with fragrant rice cooked in chicken broth. Simple but deeply flavourful.', '1 whole chicken\r\n10 cups water\r\n5 cloves garlic, smashed\r\n3 slices ginger\r\n2 stalks spring onion\r\n1 teaspoon salt\r\n2 cups long grain rice\r\n2 tablespoons chicken fat or butter\r\n3 cloves garlic, minced\r\n1 teaspoon ginger, minced\r\nSalt to taste\r\nCucumber slices for serving\r\nChili sauce for serving\r\nDark soy sauce for serving', 'Place whole chicken in a large pot.\r\nAdd water, smashed garlic, ginger slices, spring onion and salt.\r\nBring to a boil then lower heat to medium low.\r\nSimmer for 35 to 40 minutes until chicken is fully cooked.\r\nRemove chicken and place in ice cold water for 10 minutes.\r\nThis keeps the skin smooth and firm.\r\nRemove chicken and pat dry. Set aside.\r\nKeep the chicken broth in the pot.\r\nWash rice until water runs clear.\r\nHeat chicken fat or butter in a pan over medium heat.\r\nFry minced garlic and ginger until fragrant.\r\nAdd rice and stir for 2 minutes until coated.\r\nTransfer rice into a rice cooker.\r\nAdd 2 cups of chicken broth instead of plain water.\r\nAdd salt to taste. Cook the rice normally.\r\nChop chicken into serving pieces.\r\nServe chicken over fragrant rice.\r\nAdd cucumber slices on the side.\r\nServe with chili sauce and dark soy sauce.', '55', 'Lunch', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1780984995/foodify/admin_recipes/veftz3uxedbooyqbgmv4.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781011858/foodify/recipe_video/k50nfuvwbptfwe2kluii.mp4', 1, '2026-06-09 02:43:31', '2026-06-09 13:31:05'),
(44, 'Takoyaki', 'Popular Japanese street food made of round crispy balls filled with tender octopus pieces. Topped with savoury sauce, mayo and bonito flakes.', '2 cups plain flour\r\n2 and half cups dashi stock\r\n2 eggs\r\n1 tablespoon soy sauce\r\n200g cooked octopus, cut into small pieces\r\nHalf cup spring onion, chopped\r\nHalf cup pickled ginger, chopped\r\nHalf cup tempura scraps\r\nCooking oil for greasing\r\nTakoyaki sauce for topping\r\nJapanese mayonnaise for topping\r\nBonito flakes for topping\r\nDried seaweed powder for topping', 'Mix flour, dashi stock, eggs and soy sauce in a bowl.\r\nWhisk until smooth batter forms with no lumps.\r\nHeat a takoyaki pan over medium heat.\r\nBrush each hole generously with cooking oil.\r\nPour batter into each hole until almost full.\r\nPlace one piece of octopus in each hole.\r\nAdd spring onion, pickled ginger and tempura scraps on top.\r\nPour more batter to fill each hole completely.\r\nWhen the edges start to set after about 2 minutes.\r\nUse a skewer to rotate each ball 90 degrees.\r\nAdd more batter if needed to fill any gaps.\r\nContinue rotating until each ball is fully round and golden.\r\nCook for another 2 minutes until crispy all around.\r\nRemove takoyaki from the pan onto a plate.\r\nDrizzle takoyaki sauce and mayonnaise on top.\r\nTop with bonito flakes and seaweed powder.\r\nServe immediately while still hot.', '30', 'Snack', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1780985746/foodify/admin_recipes/h9jhlygee6o6jaoeewmr.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781011274/foodify/recipe_video/wvgykfrxy3jfvxosqzz5.mp4', 1, '2026-06-09 02:49:40', '2026-06-09 13:21:24'),
(45, 'Honey Garlic Salmon', 'Pan seared salmon fillet glazed with a sweet and savory honey garlic sauce. Crispy on the outside and tender on the inside.', '2 salmon fillets\r\n3 tablespoons honey\r\n3 cloves garlic, minced\r\n2 tablespoons soy sauce\r\n1 tablespoon butter\r\n1 tablespoon olive oil\r\n1 teaspoon lemon juice\r\nSalt to taste\r\nBlack pepper to taste\r\nFresh parsley for topping\r\nLemon slices for serving', 'Pat salmon fillets dry with paper towel.\r\nSeason both sides with salt and black pepper.\r\nMix honey, minced garlic, soy sauce and lemon juice in a small bowl. Set aside.\r\nHeat olive oil in a pan over medium high heat.\r\nPlace salmon fillets skin side up in the pan.\r\nSear for 4 minutes without moving.\r\nFlip the salmon to the other side.\r\nCook for another 3 minutes.\r\nReduce heat to medium low.\r\nAdd butter to the pan and let it melt.\r\nPour the honey garlic sauce over the salmon.\r\nSpoon the sauce over the salmon repeatedly for 2 minutes.\r\nThe sauce will thicken and coat the salmon nicely.\r\nRemove from heat.\r\nPlace salmon on a plate and pour remaining sauce on top.\r\nGarnish with fresh parsley and lemon slices.\r\nServe immediately with steamed rice or vegetables.', '20', 'Dinner', 'Asian', 'https://res.cloudinary.com/foodifyy/image/upload/v1780985808/foodify/admin_recipes/bbkic1whrx7nstzesqzl.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781011310/foodify/recipe_video/awsbm4si3ig58ixq4jve.mp4', 1, '2026-06-09 02:49:40', '2026-06-09 13:22:00'),
(46, 'Pisang Goreng', 'A popular Malaysian street snack of ripe bananas coated in a light and crispy batter. Best eaten hot and fresh from the fryer.', '4 ripe bananas, peeled and halved\r\n1 cup plain flour\r\n2 tablespoons rice flour\r\n1 teaspoon baking powder\r\n1 teaspoon turmeric powder\r\n1 tablespoon sugar\r\nA pinch of salt\r\n1 cup cold water\r\nOil for frying', 'Mix plain flour, rice flour, baking powder, turmeric, sugar and salt in a bowl.\r\nAdd cold water slowly and whisk until smooth batter forms.\r\nThe batter should be thick enough to coat the back of a spoon.\r\nHeat oil in a deep pan over medium heat.\r\nDip each banana piece into the batter.\r\nMake sure the banana is fully coated.\r\nCarefully lower the coated banana into the hot oil.\r\nFry for 3 to 4 minutes until golden and crispy.\r\nTurn the banana halfway through to cook evenly.\r\nRemove and drain on paper towel.\r\nServe hot immediately.', '20', 'Snack', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780981381/foodify/admin_recipes/ccskeiwdajxulcwvirzw.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781010722/foodify/recipe_video/v1mphyoagaxppsghbyrp.mp4', 1, '2026-06-09 02:55:41', '2026-06-09 13:12:13'),
(47, 'Cucur Udang', 'A classic Malaysian fritter made with fresh prawns, vegetables and a light batter. Crispy on the outside and soft inside. Perfect as a teatime snack.', '200g small prawns, peeled\r\n1 cup plain flour\r\nHalf cup rice flour\r\n1 teaspoon baking powder\r\n1 egg\r\n1 cup cold water\r\n1 onion, thinly sliced\r\n2 stalks spring onion, chopped\r\n1 red chili, sliced\r\nA pinch of turmeric powder\r\nSalt to taste\r\nOil for frying\r\nChili sauce for dipping', 'Mix plain flour, rice flour, baking powder and turmeric in a large bowl.\r\nAdd egg and cold water. Whisk until smooth batter forms.\r\nAdd salt and mix well.\r\nAdd prawns, sliced onion, spring onion and red chili into the batter.\r\nStir everything together until well combined.\r\nHeat oil in a deep pan over medium heat.\r\nScoop a spoonful of batter mixture into the hot oil.\r\nFlatten it slightly with the back of the spoon.\r\nFry for 3 minutes until the bottom is golden.\r\nFlip and fry for another 2 minutes until both sides are crispy.\r\nRemove and drain on paper towel.\r\nRepeat until all batter is used.\r\nServe hot with chili sauce for dipping.', '25', 'Snack', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780982573/foodify/admin_recipes/wdmqi8bvp59kxcdvkh32.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781011136/foodify/recipe_video/i6wwmqnrmpdibraatbep.mp4', 1, '2026-06-09 02:55:41', '2026-06-09 13:19:08'),
(48, 'Ayam Masak Kicap', 'A simple and flavourful Malaysian chicken dish cooked in a sweet and savoury soy sauce gravy. Best served with hot steamed rice.', '1 whole chicken, cut into pieces\r\n4 tablespoons dark soy sauce\r\n2 tablespoons light soy sauce\r\n1 tablespoon oyster sauce\r\n1 tablespoon sugar\r\n1 cup water\r\n3 cloves garlic, minced\r\n1 onion, sliced\r\n1 inch ginger, sliced\r\n2 tablespoons cooking oil\r\nSalt to taste\r\nRed chili for garnish\r\nSpring onion for garnish', 'Heat oil in a pan over medium heat.\r\nFry garlic, onion and ginger until fragrant.\r\nAdd chicken pieces and cook until lightly browned on all sides.\r\nPour in dark soy sauce, light soy sauce and oyster sauce.\r\nStir well to coat all the chicken pieces.\r\nAdd sugar and water. Mix everything together.\r\nCover and simmer on low heat for 20 minutes.\r\nStir occasionally until sauce thickens and chicken is fully cooked.\r\nTaste and adjust salt if needed.\r\nGarnish with red chili and spring onion.\r\nServe hot with steamed rice.', '30', 'Lunch', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780981670/foodify/admin_recipes/g4jdt4umfjs9evmswtym.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781010698/foodify/recipe_video/mwc2gzxkfuhd9cwqlzg1.mp4', 1, '2026-06-09 03:00:06', '2026-06-09 13:11:48'),
(49, 'Ayam Goreng Kunyit', 'A simple and flavourful Malaysian fried chicken marinated with turmeric and spices. Crispy on the outside and juicy inside. A classic home cooked dish.', '1 whole chicken, cut into pieces\r\n1 teaspoon turmeric powder\r\n1 teaspoon coriander powder\r\n1 teaspoon cumin powder\r\n3 cloves garlic, minced\r\n1 inch ginger, minced\r\n1 teaspoon salt\r\n1 teaspoon sugar\r\nOil for frying', 'Clean chicken pieces and pat dry with paper towel.\r\nAdd turmeric, coriander powder, cumin powder, garlic, ginger, salt and sugar.\r\nMix everything well until all chicken pieces are fully coated.\r\nLet the chicken marinate for at least 30 minutes.\r\nHeat enough oil in a deep pan over medium heat.\r\nCarefully place chicken pieces into the hot oil.\r\nFry for 8 minutes on one side until golden brown.\r\nFlip and fry for another 8 minutes on the other side.\r\nMake sure the chicken is fully cooked inside.\r\nRemove and drain on paper towel.\r\nServe hot with steamed rice.', '25', 'Lunch', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780981841/foodify/admin_recipes/ryix67gqisanvg0pjd8n.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781010427/foodify/recipe_video/vjjijkg4yia2leipxkcl.mp4', 1, '2026-06-09 03:03:30', '2026-06-09 13:07:23'),
(50, 'Asam Pedas Ikan Tenggiri', 'A classic Malay sour and spicy fish dish cooked with tamarind and aromatic spices. Rich, bold and full of flavour. Best eaten with hot steamed rice.', '500g tenggiri fish, cut into thick slices\r\n3 tablespoons tamarind paste\r\n2 cups water\r\n3 tablespoons cooking oil\r\n2 tomatoes, quartered\r\n1 ladies finger, halved\r\n3 stalks lemongrass, bruised\r\n3 kaffir lime leaves\r\n1 turmeric leaf, tied into a knot\r\nSalt to taste\r\n1 teaspoon sugar\r\n5 shallots, blended\r\n3 cloves garlic, blended\r\n1 inch galangal, blended\r\n1 inch ginger, blended\r\n4 tablespoons chili paste\r\n1 teaspoon turmeric powder', 'Mix tamarind paste with water. Stir well and strain. Set aside.\r\nHeat oil in a pot over medium heat.\r\nFry blended shallots, garlic, galangal and ginger until golden.\r\nAdd chili paste and turmeric powder.\r\nStir and cook for 3 minutes until fragrant and oil separates.\r\nAdd lemongrass and kaffir lime leaves. Stir well.\r\nPour in tamarind water and bring to a boil.\r\nAdd turmeric leaf and stir gently.\r\nSeason with salt and sugar.\r\nAdd tomatoes and ladies finger.\r\nGently place fish slices into the pot.\r\nDo not stir too much to avoid breaking the fish.\r\nSimmer on medium low heat for 10 minutes.\r\nTaste and adjust seasoning if needed.\r\nRemove from heat and serve hot with steamed rice.', '35', 'Dinner', 'Melayu', 'https://res.cloudinary.com/foodifyy/image/upload/v1780982232/foodify/admin_recipes/ytgu3uwf1zlyzh8d0amq.jpg', 'https://res.cloudinary.com/foodifyy/video/upload/v1781010390/foodify/recipe_video/jk0gm7tscsqpldjey9lw.mp4', 1, '2026-06-09 03:07:54', '2026-06-09 14:16:15');

-- --------------------------------------------------------

--
-- Table structure for table `saved_recipes`
--

CREATE TABLE `saved_recipes` (
  `user_id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','staff','admin') DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `phone`, `address`, `profile_image`, `password`, `role`, `status`, `created_at`) VALUES
(12, 'Admin', 'adminfoodify@gmail.com', '012 345 6789', NULL, NULL, '$2y$10$NYVkWM7zp9vPp2W9YRLzwuTRf0nvZzbPiIpU6eAB46esjk3I1rb2m', 'admin', 'active', '2026-06-10 16:21:52'),
(13, 'Staff', 'staffoodify@gmail.com', '012 345 6789', NULL, NULL, '$2y$10$SndD87yDRZqwH6TuI2SICOZs5n6kdY07WGwlZqv3zp/9FarrrFCK.', 'staff', 'active', '2026-06-10 16:23:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `created_recipes`
--
ALTER TABLE `created_recipes`
  ADD PRIMARY KEY (`cr_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_id`,`item_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`recipe_id`);

--
-- Indexes for table `saved_recipes`
--
ALTER TABLE `saved_recipes`
  ADD UNIQUE KEY `unique_save` (`user_id`,`recipe_id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `created_recipes`
--
ALTER TABLE `created_recipes`
  MODIFY `cr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `recipe_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `created_recipes`
--
ALTER TABLE `created_recipes`
  ADD CONSTRAINT `created_recipes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `saved_recipes`
--
ALTER TABLE `saved_recipes`
  ADD CONSTRAINT `saved_recipes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_recipes_ibfk_2` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
