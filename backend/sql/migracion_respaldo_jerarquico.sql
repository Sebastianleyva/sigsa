ALTER TABLE `salidas`
  ADD COLUMN `solicita_respaldo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `id_autoriza`;
