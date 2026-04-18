CREATE TABLE `llx_colabor_type_annotation` (
  `rowid` integer AUTO_INCREMENT PRIMARY KEY,
  `code` varchar(15) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `active` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

ALTER TABLE `llx_colabor_type_annotation`
  ADD PRIMARY KEY (`rowid`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `llx_colabor_type_annotation`
--
ALTER TABLE `llx_colabor_type_annotation`
  MODIFY `rowid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;



INSERT INTO `llx_colabor_type_annotation` (`rowid`, `code`, `label`, `active`) VALUES
(1, 'NOT', 'Nota general',  1),
(2, 'COM', 'Comunicaión',  1),
(3, 'SEG', 'Seguimiento',  1),
(4, 'PRO', 'Problema/Bloqueo',  1),
(5, 'CAM', 'Cambio solicitado',  1);

