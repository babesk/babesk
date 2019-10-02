CREATE TABLE IF NOT EXISTS `schbascoresubjects` (
  `ID` int(11) NOT NULL,
  `gradelevel` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
);

ALTER TABLE `schbascoresubjects`
  ADD PRIMARY KEY (`ID`);