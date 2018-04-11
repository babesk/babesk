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
                dataFill(json.user, json.subjects)

            })
    })
    $('.dropdown-item').trigger('click');

    $('#search_btn').on('click', function (event) {
        user = $('#user_search').val();
        $.postJSON(
            'index.php?section=System|SpecialCourse&action=3',
            {
                'user': user
            },
            function (res, textStatus, jqXHR) {
                json = JSON.parse(res);
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
                if(user.special_course.split('|').indexOf(subject.abbreviation)>-1){
                    name_str = user.ID+"|"+subject.abbreviation;
                    $('input[name="'+name_str+'"]').prop('checked', true);
                }
            })
        })
    }
})