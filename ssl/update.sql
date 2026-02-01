ALTER TABLE `mod_asciossl` ADD `whmcs_service_id` INT;
ALTER TABLE `mod_asciossl` ADD `commonName` VARCHAR(2048);
ALTER TABLE `mod_asciossl` ADD `csr` VARCHAR(2048);
ALTER TABLE `mod_asciossl` ADD `webserver` VARCHAR(2048);
ALTER TABLE `mod_asciossl` ADD `approvalEmail` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `ownerTitle` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `ownerFirstName` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `ownerLastName` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `ownerCompanyName` VARCHAR(512);
ALTER TABLE `mod_asciossl` ADD `ownerEmail` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `ownerPhone` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `ownerAddress1` VARCHAR(512);
ALTER TABLE `mod_asciossl` ADD `ownerAddress2` VARCHAR(512);
ALTER TABLE `mod_asciossl` ADD `ownerCity` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `ownerState` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `ownerPostcode` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `ownerCountry` VARCHAR(256);

ALTER TABLE `mod_asciossl` ADD `adminTitle` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `adminFirstName` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `adminLastName` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `adminCompanyName` VARCHAR(512);
ALTER TABLE `mod_asciossl` ADD `adminEmail` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `adminPhone` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `adminAddress1` VARCHAR(512);
ALTER TABLE `mod_asciossl` ADD `adminAddress2` VARCHAR(512);
ALTER TABLE `mod_asciossl` ADD `adminCity` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `adminState` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `adminPostcode` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `adminCountry` VARCHAR(256);

ALTER TABLE `mod_asciossl` ADD `techTitle` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `techFirstName` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `techLastName` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `techCompanyName` VARCHAR(512);
ALTER TABLE `mod_asciossl` ADD `techEmail` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `techPhone` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `techAddress1` VARCHAR(512);
ALTER TABLE `mod_asciossl` ADD `techAddress2` VARCHAR(512);
ALTER TABLE `mod_asciossl` ADD `techCity` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `techState` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `techPostcode` VARCHAR(256);
ALTER TABLE `mod_asciossl` ADD `techCountry` VARCHAR(256);
ALTER TABLE `mod_asciossl` CHANGE `id` `id` INT(8) NOT NULL AUTO_INCREMENT;

CREATE TABLE `mod_asciossl_sans` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `name` int(11) NOT NULL,
  `approvalEmail` varchar(256) NOT NULL
);

ALTER TABLE `mod_asciossl_sans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `whmcs_service_id` (`service_id`);

ALTER TABLE `mod_asciossl_sans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
