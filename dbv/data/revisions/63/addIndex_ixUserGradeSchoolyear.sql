ALTER TABLE `SystemAttendances`
	ADD UNIQUE `ixUserGradeSchoolyear` (`userId`, `gradeId`, `schoolyearId`);
