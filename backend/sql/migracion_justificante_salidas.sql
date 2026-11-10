ALTER TABLE `salidas`
  ADD COLUMN `requiere_justificante` TINYINT(1) NOT NULL DEFAULT 0 AFTER `solicita_respaldo`;

UPDATE `salidas`
SET `requiere_justificante` = COALESCE(`justificante`, 0);

ALTER TABLE `salidas`
  CHANGE COLUMN `justificante` `justificante_entregado` TINYINT(1) DEFAULT NULL,
  MODIFY COLUMN `fecha_llegada` DATE DEFAULT NULL;

UPDATE `salidas`
SET `justificante_entregado` = NULL;
