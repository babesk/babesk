$(document).ready(function() {

    activePage = 1;
    gradelevel = 5;
    amountPages = 1;

    $('#page-select').on('click', 'a', function(ev) {
        $this = $(this);
        if($this.hasClass('first-page')) {
            var page = 1;
        }
        else if($this.hasClass('last-page')) {
            console.log(amountPages);
            var page = amountPages;
        }
        else {
            var page = $(this).text();
        }
        activePage = page;
        dataFetch(page);
    });

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
            gradelevel = $item.data('name');
        }
        else {
            gradelevel = false;
        }
        activePage = 1;
        dataFetch();

    })
    $('.dropdown-item').first().trigger('click');

    function dataFetch() {
        $.postJSON(
            'index.php?section=System|SpecialCourse&action=5',
            {
                'gradelevel': gradelevel,
                'filter': 'name',
                'activePage': activePage
            },
            function (res, textStatus, jqXHR) {
                json = JSON.parse(res);
                dataFill(json.user, json.subjects, json.pagecount)

            })
    }

    $('#search_btn').on('click', function (event) {
        user = $('#user_search').val();
        $.postJSON(
            'index.php?section=System|SpecialCourse&action=3',
            {
                'user': user
            },
            function (res, textStatus, jqXHR) {
                json = JSON.parse(res);
                dataFill(json.user, json.subjects, json.pagecount);
            }
        )
    })

    function dataFill(users, subjects, pagecount) {
        pageSelectorUpdate(pagecount);
        var html = microTmpl($('#user-table-template').html(),
            {
                'users':users,
                'subjects':subjects
            });
        $('#user-table-body').html(html);
        subjectList = '<th align="center"><a class="column" data-name="forename">Vorname</a></th><th align="center"><a class="column" data-name="name">Name</a></th>'
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

        $('.column').on('click', function (event) {
            grade = $('li.active > a.dropdown-item').data('name');
            column = $(event.target).data('name');

            $.postJSON(
                'index.php?section=System|SpecialCourse&action=5',
                {
                    'gradelevel': grade,
                    'filter': column
                },
                function (res, textStatus, jqXHR) {
                    json = JSON.parse(res);
                    dataFill(json.user, json.subjects)

                })
        })
    }

    function pageSelectorUpdate(pagecount) {
        amountPages = pagecount;
        console.log(amountPages);

        var amountSelectorsDisplayed = 9;
        var startPage = activePage - Math.floor(amountSelectorsDisplayed / 2);
        if(startPage < 1) {
            startPage = 1;
        }
        if(activePage == 1) {
            $pager= $(
                '<li class="disabled"><a class="first-page">&laquo;</a></li>'
            );
            $('#relative-pager-prev').addClass('disabled');
        }
        else {
            $pager= $('<li><a href="#" class="first-page">&laquo;</a></li>');
            $('#relative-pager-prev').removeClass('disabled');
        }
        for(var i = startPage; i <= pagecount && i < startPage + amountSelectorsDisplayed; i++) {
            if(i == activePage) {
                $pager.append('<li class="active"><a href="#">' + i + '</a></li>');
            }
            else {
                $pager.append('<li><a href="#">' + i + '</a></li>');
            }
        }
        if(activePage == pagecount) {
            $pager.append(
                '<li class="disabled"><a href="#" class="last-page">&raquo;</a></li>'
            );
            $('#relative-pager-next').addClass('disabled');
        }
        else {
            $pager.append('<li><a href="#" class="last-page">&raquo;</a></li>');
            $('#relative-pager-next').removeClass('disabled');
        }
        $('#page-select').html($pager.outerHtml());
    }


})