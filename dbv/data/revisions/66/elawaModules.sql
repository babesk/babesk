CALL moduleAddNewByPath(
	"Categories", 1, 0,
	"administrator/Elawa/Categories/Categories.php",
	"Elawa", "root/administrator/Elawa",
	@newModuleId
);
INSERT INTO SystemGroupModuleRights (groupId, moduleId)
	VALUES (3, @newModuleId);
CALL moduleAddNewByPath("MeetingTimes", 1, 0, "administrator/Elawa/MeetingTimes/MeetingTimes.php", "Elawa", "root/administrator/Elawa", @newModuleId);
INSERT INTO SystemGroupModuleRights (groupId, moduleId) VALUES (3, @newModuleId);
CALL moduleAddNewByPath("Rooms", 1, 1, "administrator/System/Rooms/Rooms.php", "System", "root/administrator/System", @newModuleId);
INSERT INTO SystemGroupModuleRights (groupId, moduleId) VALUES (3, @newModuleId);
UPDATE  `SystemModules` SET  `displayInMenu` =  '0' WHERE  `SystemModules`.`name` ='GenerateHostPdf';
UPDATE  `SystemModules` SET  `displayInMenu` =  '0' WHERE  `SystemModules`.`name` ='SetHostGroup';
UPDATE  `SystemModules` SET  `displayInMenu` =  '0' WHERE  `SystemModules`.`name` ='SetSelectionsEnabled';

