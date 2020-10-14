$(document).ready(function() {

	(function(){
		var activePage = 1;
		var amountPages = 0;

		$('[title]').tooltip();
		newDataFetch();

		// When searching or entering a new row-count, refresh on enter
		$('#books-per-page, #filter').on('keyup', function(event) {
			activePage = 1;   //Reset pagenumber
			event.preventDefault();
			if(event.which == 13) {
				newDataFetch();
			}
		});

		$('ul#page-select').on('click', 'li:not(.disabled) > a', function(event) {
			activePage = parseInt($(event.target).text());
			newDataFetch();
		});

		function newDataFetch() {

			var pagenumber = activePage;

			$.postJSON(
				"index.php?module=administrator|Schbas|Booklist|ShowBooklist&ajax=1",
				{
					"pagenumber": pagenumber - 1,
					"filterFor": $('#filter').val(),
					"booksPerPage": $('#books-per-page').val()
				},
				function(res) {
					tableRefresh(res.books);
					paginatorRefresh(res.pagecount);
					$('[title]').tooltip();
				}
			);
		};

		function tableRefresh(books) {

			function td(el) {
				return "<td>" + el + "</td>";
			}

			var tbody = $('#booklist tbody');
			$('#booklist tbody').html('');
			$.each(books, function(ind, book) {
				var row = microTmpl($('#booklist-row-template').html(), book)
				tbody.append(row);
			});
		};

		function paginatorRefresh(pagecount) {

			var amountPagesDisplayed = 9;
			var startPage = activePage - Math.floor(amountPagesDisplayed / 2);
			if(startPage < 1) {
				startPage = 1;
			}
			var html = microTmpl(
				$('#paginator-template').html(),
				{
					"startPage": startPage,
					"pagecount": pagecount,
					"amountDisplayed": amountPagesDisplayed,
				}
			);
			$('#page-select').html(html);
		};

	})();

});
