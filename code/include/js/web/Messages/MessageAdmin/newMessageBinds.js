$('.templateSelection').on('click', function(event) {

	var templateId = $(this).attr('id');

	$.ajax({
		type: "POST",
		url: 'index.php?section=Messages|MessageAdmin&action=fetchTemplateAjax',
		data: {
			'templateId': templateId
		},
		success: function(data) {
			if(data == 'errorFetchTemplate') {
				alert('Konnte das Template nicht abrufen!');
			}
			templateData = $.parseJSON(data);
			CKEDITOR.instances['cke'].setData(templateData.text);
			$('#messagetitle').val(templateData.title);
		},
		error: function(data) {
			alert('Konnte das Template nicht abrufen!');
		}
	});
});