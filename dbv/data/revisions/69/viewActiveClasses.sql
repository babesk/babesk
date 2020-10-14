CREATE OR REPLACE VIEW UserActiveClass AS
  SELECT u.*, a.gradelevel, a.label FROM SystemUsers u
    LEFT JOIN (SELECT a.userId, g.gradelevel, g.label FROM SystemAttendances a
      JOIN SystemGrades g ON (a.gradeId=g.ID)
      JOIN SystemSchoolyears y ON (y.ID = a.schoolyearId)
    WHERE y.active=1) a ON (u.ID = a.userId)