# @cjsx React.DOM

$(document).ready () ->
	$("#allowRegistration").bootstrapSwitch()
	$("#isOptional").bootstrapSwitch()
	$("#category-select").multiselect({
		selectAllText: "Alle auswählen"
		buttonContainer: '<div class="btn-group" />'
		checkboxName: 'categories[]'
	})

