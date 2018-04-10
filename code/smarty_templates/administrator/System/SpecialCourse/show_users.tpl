{extends file=$SpecialCourseParent}{block name=content}

<script type="text/template" id="user-table-template">
	<% for(var i = 0; i < users.length; i++) { %>
		<tr>
			<td align="center"><%= users[i].forename %></td>
			<td align="center"><%= users[i].name %></td>
                <% for(var j=0; j<subjects.length;j++) { %>
			<td><input type="checkbox" name="<%=users[i].ID%>|<%=subjects[j].abbreviation%>"/></td>
                <% } %>
		</tr>
	<% } %>
</script>

	<div class="dropdown">
		<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Jahrgang <span class="caret"></span> </button>
		<ul class="dropdown-menu">
            {foreach $gradelevel as $grade}
				<li><a data-name="{$grade.gradelevel}" class="dropdown-item">{$grade.gradelevel}</a></li>
            {/foreach}
		</ul>
	</div>

	<br>

	<div class="row col-sm-12 col-md-5 col-lg-7">
		<span class="input-group filter-container">
			<input type='text' id='user_search' class="form-control">
			<span class="input-group-btn">
				<button type="button" id="search_btn" class="btn btn-default">
					<span class="fa fa-search fa-fw"></span>
				</button>
			</span>
		</span>
	</div>



<form action="index.php?section=System|SpecialCourse&action=4"
	method="post" onsubmit="submit()">
<table class="table table-striped table-hover">
	<thead>
		<tr id="subjects-list">

		</tr>
	</thead>
	<tbody id="user-table-body">

	</tbody>
</table>
	<br> <input id="submit" onclick="submit()" type="submit" value="Speichern" />
</form>

{/block}

{block name="js_include" append}
<script type="text/javascript"
		src="{$path_js}/vendor/paginator/jquery.bootpag.min.js">
</script>
<script type="text/javascript"
		src="{$path_js}/vendor/bootbox.min.js">
</script>

<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>
<script type="text/javascript" src="{$path_js}/custom-base.js"></script>

<script>
    $(document).ready(function() {
        $('.dropdown-item').on('click',function (event) {
            function removeActiveFromFilters() {
                $items = $('.dropdown-menu li.active');
                $items.removeClass('active');
            }

            var $item = $(event.target);
            var isActive = $item.parent('li').hasClass('active');
            removeActiveFromFilters();
            if(!isActive) {
                $item.parent('li').addClass('active');
                specialFilter = $item.data('name');
            }
            else {
                specialFilter = false;
            }
            activePage = 1;
            $.postJSON(
                'index.php?section=System|SpecialCourse&action=5',
			{
				'gradelevel': specialFilter
			},
			function (res, textStatus, jqXHR) {
				json = JSON.parse(res);
				console.log(json);
				dataFill(json.user, json.subjects)

            })
        })
		$('.dropdown-item').trigger('click');

        $('#search_btn').on('click', function (event) {
            user = $('#user_search').val();
            console.log(user);
			$.postJSON(
                'index.php?section=System|SpecialCourse&action=3',
				{
				    'user': user
                },
				function (res, textStatus, jqXHR) {
					json = JSON.parse(res);
					console.log(json);
					dataFill(json.user, json.subjects);
                }
			)
        })

		function dataFill(users, subjects) {
            var html = microTmpl($('#user-table-template').html(),
                {
                    'users':users,
                    'subjects':subjects
                });
            $('#user-table-body').html(html);
            subjectList = '<th align="center"><a href="index.php?section=System|SpecialCourse&action=3&filter=forename">Vorname</a></th><th align="center"><a href="index.php?section=System|SpecialCourse&action=3&filter=name">Name</a></th>'
            subjects.forEach(function (subject) {
                subjectList=subjectList+"<th>"+subject.abbreviation+"</th>";
            });
            $('#subjects-list').html(subjectList);
            users.forEach(function (user) {
				subjects.forEach(function (subject) {
				    console.log(user);
					if(user.special_course.split('|').indexOf(subject.abbreviation)>-1){
						name_str = user.ID+"|"+subject.abbreviation;
					    $('input[name="'+name_str+'"]').prop('checked', true);
                    }
                })
            })
        }
    })
</script>

{/block}