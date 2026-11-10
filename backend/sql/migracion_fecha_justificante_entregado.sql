ALTER TABLE `salidas`
  ADD COLUMN `fecha_justificante_entregado` DATETIME DEFAULT NULL
  AFTER `justificante_entregado`;
