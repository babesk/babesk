CALL moduleAddNewByPath(
	"RechargeUser", 1, 0,
	"administrator/Babesk/Recharge/Recharge/RechargeUser.php",
	"Recharge", "root/administrator/Babesk/Recharge",
	@newModuleId
);
INSERT INTO SystemGroupModuleRights (groupId, moduleId)
	VALUES (3, @newModuleId);