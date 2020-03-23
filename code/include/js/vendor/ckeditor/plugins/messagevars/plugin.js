CKEDITOR.plugins.add( 'messagevars',
		{
			init: function( editor )
			{
				editor.addCommand( 'forename',
					{
						exec : function( editor )
						{
								editor.insertHtml( "{vorname}" );
						}
					});
				editor.addCommand( 'name',
					{
						exec : function( editor )
						{
								editor.insertHtml( "{name}" );
						}
					});
				editor.addCommand( 'grade',
						{
							exec : function( editor )
							{
									editor.insertHtml( "{klasse}" );
							}
						});
				editor.addCommand( 'messageBarcode',
					{
						exec:function(editor)
						{
							editor.insertHtml("{barcode}");
						}
					});
                editor.addCommand( 'ID',
                    {
                        exec:function(editor)
                        {
                            editor.insertHtml("{kartennummer}");
                        }
                    });
				editor.ui.addButton( 'messageForename',
					{
						label: 'Platzhalter f&#252;r: Vorname',
						command: 'forename',
						icon: this.path + 'forename.png'
					});
				editor.ui.addButton( 'messageName',
					{
						label: 'Platzhalter f&#252;r: Name',
						command: 'name',
						icon: this.path + 'name.png'
					});
				editor.ui.addButton( 'messageGrade',
					{
						label: 'Platzhalter f&#252;r: Klasse',
						command: 'grade',
						icon: this.path + 'grade.png'
					});
                editor.ui.addButton( 'messageCardID',
                    {
                        label: 'Platzhalter f&#252;r: Kartennummer',
                        command: 'ID',
                        icon: this.path + 'grade.png'
                    });
			}
		} );