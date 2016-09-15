ALTER TABLE `BabeskPriceClasses` ADD `soliprice` DECIMAL(6,2) NOT NULL AFTER `color`;
INSERT INTO `SystemGlobalSettings` (`id`, `name`, `value`) VALUES (NULL, 'seperateSoliPrices', '0');