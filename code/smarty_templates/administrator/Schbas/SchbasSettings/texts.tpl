{extends file=$schbasSettingsParent}{block name=content}

<h3 class="module-header">Infotexte</h3>

<form action="index.php?section=Schbas|SchbasSettings&amp;action=10"
	method="post">

	<div class="form-group">
		<label for="gradeValueSelection">Bitte Jahrgang ausw&auml;hlen:</label>
		<select id="gradeValueSelection" name="grade" class="form-control">
			<option value="5">5</option>
			<option value="6">6</option>
			<option value="7">7</option>
			<option value="8">8</option>
			<option value="9">9</option>
			<option value="10">10</option>
			<option value="11">11</option>
			<option value="12">12</option>
			<option value="13">13</option>
		</select>
	</div>

	<div class="form-group">
		<label for="messagetitle">Titel erster Text:</label>
			<input id="messagetitle" type="text" name="messagetitle"
				class="form-control" value="" />
		<label>Text 1:</label>
		<textarea class="ckeditor" name="messagetext"></textarea>
	</div>

	<div class="form-group">
		<label for="messagetitle2">Titel zweiter Text:</label>
		<input id="messagetitle2" type="text" name="messagetitle2"
			class="form-control" value="" />
		<label>Text 2:</label>
		<textarea class="ckeditor" name="messagetext2"></textarea>
	</div>

	<div class="form-group">
		<label for="messagetitle3">Titel dritter Text:</label>
		<input id="messagetitle3" type="text" name="messagetitle3"
			class="form-control" value="" />
		<label>Text 3:</label>
		<textarea class="ckeditor" name="messagetext3"></textarea>
	</div>

	<input id="submit" type="submit" class="btn btn-primary" value="Speichern" />
</form>

{/block}

{block name=js_include append}

<script type="text/javascript" src="{$path_js}/vendor/ckeditor/ckeditor.js">
</script>

<script type="text/javascript">

$(function () {

	$('#gradeValueSelection').on('click', function(event) {
		var templateId = $(this).children('option:selected').val();
		var textIdOne = 'textOne';
		var textIdTwo = 'textTwo';
		var textIdThree = 'textThree';
		$.ajax({
			type: "POST",
			url: 'index.php?section=Schbas|SchbasSettings&action=fetchTextsAjax',
			data: {
				'templateId': templateId,
				'textId': textIdOne
			},
			success: function(data) {
				if(data == 'errorFetchTemplate') {
					alert('Konnte das Template nicht abrufen!');
				}
				templateData = $.parseJSON(data);
				CKEDITOR.instances['messagetext'].setData(templateData.text);
				$('#messagetitle').val(templateData.title);
			},
			error: function(data) {
				alert('Konnte das Template nicht abrufen!');
			}
		});


		$.ajax({
			type: "POST",
			url: 'index.php?section=Schbas|SchbasSettings&action=fetchTextsAjax',
			data: {
				'templateId': templateId,
				'textId': textIdTwo
			},
			success: function(data) {
				if(data == 'errorFetchTemplate') {
					alert('Konnte das Template nicht abrufen!');
				}
				templateData = $.parseJSON(data);
				CKEDITOR.instances['messagetext2'].setData(templateData.text);
				$('#messagetitle2').val(templateData.title);
			},
			error: function(data) {
				alert('Konnte das Template nicht abrufen!');
			}
		});

		$.ajax({
			type: "POST",
			url: 'index.php?section=Schbas|SchbasSettings&action=fetchTextsAjax',
			data: {
				'templateId': templateId,
				'textId': textIdThree
			},
			success: function(data) {
				if(data == 'errorFetchTemplate') {
					alert('Konnte das Template nicht abrufen!');
				}
				templateData = $.parseJSON(data);
				CKEDITOR.instances['messagetext3'].setData(templateData.text);
				$('#messagetitle3').val(templateData.title);
			},
			error: function(data) {
				alert('Konnte das Template nicht abrufen!');
			}
		});

	});
});
</script>


{/block}