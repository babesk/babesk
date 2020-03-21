displaySelectHostGroup = function() {
    var form, hosts, data;
    hosts = "[]";
    data = document.getElementById('groups').dataset.groups;
    console.log(data);
    hosts = JSON.parse(data);
    form = "<form name='changeHostGroup' method='post' action='index.php?module=administrator|Messages|MessageAuthor&action=changeAuthorGroup'><div class='row'> <div class='col-md-4'> <label for='host-group-select'>Gruppe auswählen:</label> </div> <div class='col-md-8'> <select id='host-group-select' name='group'></form>";
    hosts.forEach(function(i, j, k){
        form += "<option value='"+i.ID+"'";
        if(i.selected == true)
            form += " selected ";
        form += ">"+i.name+"</option>";
    });
    form += "</select> </div> </div>";
    return bootbox.dialog({
        title: "Ändern der Gruppe der Lehrer",
        message: form,
        "buttons": {
            success: {
                label: "Gruppe ändern",
                className: "btn-success",
                callback: function() {
                    document.changeHostGroup.submit();
                }
            }
        }
    });
};

$('a#select-host-group-button').on('click', function(event) {
    return displaySelectHostGroup();
});