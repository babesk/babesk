$(document).ready(function() {

	registerUser();

	function registerUser() {

		$('#issoli').bootstrapSwitch();
		//Initialize the correct display of passwords
		passwordOptionOnChange();

		$('#password-options-container').on('change', function(ev) {
			passwordOptionOnChange();
		});

		$('.group-identifier').on('click', function(ev) {
			$(this).toggleClass('label-default label-success active');
		});

		$('#usergroups .expand').on('click', function(ev) {
			$(this).children('.fa').toggleClass('fa-plus fa-minus');
		});

		$('#grade-schoolyears').on('click', '.grade-schoolyear-remove',
			function(ev) {
				$(this).closest('.input-group').fadeOut({complete: function() {
					$(this).closest('.input-group').remove();
				}
			});
		});

		/**
		 * Submit the modal with which the user is added to a grade and schoolyear
		 */
		$('#grade-schoolyear-submit').on('click', function(ev) {

			var $modal = $(this).closest('#grade-schoolyear-modal');
			var gradeId = $modal.find('#modal-gradeid option:selected').val();
			var schoolyearId = $modal.find('#modal-schoolyearId option:selected')
				.val();
			var schoolyearName = $modal.find('#modal-schoolyearId option:selected')
				.text();
			var $snippet = $($('#grade-schoolyear-snippet').html());
			$snippet.find('select.grade-selector option[value=' + gradeId + ']')
				.prop("selected", true);
			$snippet.find('select.schoolyear-selector option[value=' + schoolyearId
					+ ']')
				.prop("selected", true);

			$existingSchoolyearWithValue = $(
				'#grade-schoolyears .schoolyear-selector option[value="' +
					schoolyearId + '"]:selected'
			);
			if($existingSchoolyearWithValue.length) {
				$('#grade-schoolyear-modal').modal('hide');
				bootbox.confirm('Der Schüler wurde bereits in diesem Schuljahr eingetragen ("' + schoolyearName +
					'"). Wollen sie ihn wirklich nochmal eintragen?',
					function(res) {
						if(res) {
							submitGradeSchoolyears();
						}
					}
				);
			}
			else {
				$('#grade-schoolyear-modal').modal('hide');
				submitGradeSchoolyears();
			}

			function submitGradeSchoolyears() {
				$('#grade-schoolyears').append($snippet);
			}
		});

		/**
		 * Shows / hides the custom-password fields based on the toggle
		 * @param  {Boolean} isChecked If the presetpassword-toggle is checked or
		 *                             not
		 */
		function customPasswordCheckDisabled(isChecked, animate) {
			$fields = $('#password, #password-repeat');
			$fields.prop('disabled', isChecked);
			if(isChecked) {
				if(animate) {
					$fields.closest('.form-group').slideUp();
				}
				else {
					$fields.closest('.form-group').hide();
				}
				// Remove error-messages if exist from password-field
				$('span[for="password"],span[for="password-repeat"]').remove();
				$fields.closest('.form-group').removeClass('has-error');
			}
			else {
				if(animate) {
					$fields.closest('.form-group').slideDown();
				}
				else {
					$fields.closest('.form-group').show();
				}
			}
		};

		function passwordOptionIdGetChecked() {

			var $checked = $('#password-options-container input:checked');
			return $checked.attr('id');
		}

		function passwordOptionOnChange() {

			var id = passwordOptionIdGetChecked();
			if(id == 'password-option-preset') {
				passwordManualInputFieldsClear();
				passwordManualInputFieldsHide();
			}
			else if(id == 'password-option-birthday') {
				passwordManualInputFieldsClear();
				passwordManualInputFieldsHide();
			}
			else if(id == 'password-option-manual') {
				passwordManualInputFieldsClear();
				passwordManualInputFieldsShow();
			}
			$fields = $('#password, #password-repeat');
		}

		function passwordManualInputFieldsClear() {

			$fields = $('#password, #password-repeat');
			$fields.val('');
		}

		function passwordManualInputFieldsShow() {

			$fields = $('#password, #password-repeat');
			$fields.closest('.form-group').show();
			$fields.prop('disabled', false);
		}
		function passwordManualInputFieldsHide() {

			$fields = $('#password, #password-repeat');
			$('span[for="password"],span[for="password-repeat"]').remove();
			$fields.closest('.form-group').removeClass('has-error');
			$fields.closest('.form-group').hide();
			$fields.prop('disabled', true);
		}

		/*==========  Validation  ==========*/

		$('#register-form').validate({
			rules: {
				"password-repeat": {equalTo: "#password"}
			},
			messages: {
				"password-repeat": {
					equalTo: "Passwort-Wiederholung muss mit Passworteingabe " +
						"übereinstimmen!"
				}
			},
			submitHandler: function(form) {

				if(!$('#usergroups .group-identifier.active').length) {
					bootbox.confirm('Es wurden keine Benutzergruppen ausgewählt. Der neue Benutzer wird sich deswegen nicht anmelden und keine der Funktionen nutzen können. Möchten sie trotzdem fortfahren?',
						function(res) {
							if(res) {
								upload();
							}
					});
				}
				else {
					upload();
				}

			},
			invalidHandler: function(event, validator) {
				// 'this' refers to the form
				var errors = validator.numberOfInvalids();
				if (errors) {
					var message = (errors == 1)
						? 'Sie haben in einem Feld eine inkorrekte Eingabe gemacht.'
						: 'Sie haben in ' + errors + ' Feldern eine inkorrekte Eingabe gemacht';
					toastr['error'](message, 'Eingabefehler');
				}
			},
			ignore: "disabled"
		});

		/*==========  Upload  ==========*/

		function upload() {
			$('#form-submit').button('loading');
			var groups = [];
			$('#usergroups .group-identifier.active').each(function (i, el) {
				groups.push($(el).attr('groupId'));
			});
			var schoolyearGrades = [];
			$('#grade-schoolyears .form-group').each(function(i, el) {
				schoolyearGrades.push({
					'schoolyearId': $(el).find('.schoolyear-selector option:selected').val(),
					'gradeId': $(el).find('.grade-selector option:selected').val()
				});
			});

			var presetPasswordToggle = false;
			var pwId = passwordOptionIdGetChecked();
			if(pwId == 'password-option-preset') {
				presetPasswordToggle = true;
			}
			else if(pwId == 'password-option-birthday') {
				var birthday = $('#birthday').val();
				$('#password, #password-repeat').val(birthday);
			}

			$.ajax({
				type: 'POST',
				url: 'index.php?module=administrator|System|User|Register',
				data: {
					'forename': $('#forename').val(),
					'lastname': $('#lastname').val(),
					'username': $('#username').val(),
					'presetPasswordToggle': presetPasswordToggle,
					'password': $('#password').val(),
					'email': $('#email').val(),
					'telephone': $('#telephone').val(),
					'birthday': $('#birthday').val(),
					'pricegroupId': $('#pricegroupId option:selected').val(),
					'groups': groups,
					'schoolyearAndGradeData': schoolyearGrades,
					'credits': $('#credits').val(),
					'cardnumber': $('#cardnumber').val(),
					'isSoli': $('#issoli').bootstrapSwitch('state')
				},
				success: function(data) {
					console.log(data);
					try {
						data = JSON.parse(data);
					} catch(e) {
						toastr['error']('Konnte die Serverantwort nicht parsen');
						$('#form-submit').button('error');
						return;
					}
					if(data.value == 'success') {
						toastr['success'](data.message);
						$('#form-submit').button('complete');
					}
					else if(data.value == 'error') {
						toastr['error'](data.message);
						$('#form-submit').button('error');
					}
					else {
						toastr['error']('Konnte die Serverantwort nicht lesen');
						$('#form-submit').button('error');
					}

				},
				error: function(data) {
					toastr['error']('Nope!');
					$('#form-submit').button('error');

				}

			});
		};
	}
});