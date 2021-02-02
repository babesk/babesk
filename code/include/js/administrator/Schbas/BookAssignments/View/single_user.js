$(document).ready(function() {

    $('.deleteAssignment').on('click', function (event) {
        var toDelete = $(this).attr('id');
        console.log(toDelete)
        $.ajax({
            method: 'POST',
            url: 'index.php?module=administrator|Schbas|BookAssignments|Delete',
            data: {
                bookAssignmentId: toDelete
            },
            success: function(data) {
                location.reload();
            },
            error: function(data) {
                alert('Fehler beim Löschen');
            }
        })
    })
    $('#generateNew').on('click', function (event) {
        var userID = $('#userID').val();
        console.log(userID)
        $.ajax({
            method: 'POST',
            url: 'index.php?module=administrator|Schbas|BookAssignments|Generate',
            data: {
                userId: userID
            },
            success: function(data) {
                location.reload();
            },
            error: function(data) {
                alert('Fehler beim Generieren');
            }
        })
    })
    
})