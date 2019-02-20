function getBaseURL() {
    url = document.location.href;
    i = url.indexOf("/index.php/");
    base = url.substring(0, i + 1);

    return base;
}

function escapeHtml(unsafe) {
    return unsafe
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;");
 }

function showDeleteUserDlg(id) {
    var res = window.confirm("Are you sure you want to delete this user?");
    if (res == true) {
        $("#loading_message").addClass("show").removeClass("hidden");
        document.location.href = getBaseURL() + 'index.php/admin/Users/delete/' + id;
    }
}

function showDeleteProjectDlg(id) {
    var res = window.confirm("Are you sure you want to delete this project?");
    if (res === true) {
        document.location.href = getBaseURL() + 'index.php/admin/Projects/delete/' + id;
    }
}

function getTableFields(num, table, field) {
    base = getBaseURL();
    $.ajax({
        url: base + 'index.php/Ajax/get_table_fields/' + table,
        type: 'POST',
        dataType: 'json',
        success: function (data) {
            //$("field_"+num).empty();
            $("#field_" + num).empty();
            $("#oper_" + num).empty();
            opt = "<option value=''></option> ";
            for (i = 0; i < data.length; i++) {
                opt += "<option value='" + data[i]+"'"
                +(field && data[i] == field ? ' selected ' : '')
                + ">" + data[i] + "</option> ";
            }
            console.log("setting option for item "+num+" Opt: "+opt);
            $("#field_" + num).html(opt);
        } // End of success function of ajax form
    }); // End of ajax call    
}

function getOperators(num, table, field, op) {
    base = getBaseURL();
    enField = encodeURI(field);
    $.ajax({
        url: base + 'index.php/Ajax/get_operators/' + table + '/' + enField,
        type: 'POST',
        dataType: 'json',
        success: function (data) {
            //$("field_"+num).empty();
            $("#oper_" + num).empty();
            opt = "";
            for (i = 0; i < data.length; i++) {
                opt += "<option value='" + data[i]+"'"
                +(op && data[i] == op ? ' selected ' : '')
                + ">" + escapeHtml(data[i]) + "</option> ";
            }
            console.log(opt);
            $("#oper_" + num).html(opt);
        } // End of success function of ajax form
    }); // End of ajax call   
}

function addSearchClause() {
    id = $(".search_clause").last().attr("id");
    n = parseInt(id.substring(14,id.length))+1;
    var clause = "<div class='row search_clause' id='search_clause_" + n + "'><div class='col-md-2 col-md-offset-2'><select id='table_" + n + "' name='table_" + n + "' class='form-control'>"
            + "<option value=''></option></select></div><div class='col-md-2'><select id='field_" + n + "' name='field_" + n + "' class='form-control'></select>"
            + "</div><div class='col-md-2'><select id='oper_" + n + "' name='oper_" + n + "' class='form-control'></select></div><div class='col-md-2'>"
            + "<input type='text' name='value_" + n + "' id='value_" + n + "' class='form-control'/></div><div class='col-md-1'><button type='button' id='delete_clause_"+n+"' class='close' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div></div><div class='row'></div>";
    // Add the HTML
    
    $(".search_clause").last().after(clause);
    initializeSelect(n);
    // Set the event handlers
    $("#table_"+n).change(function (event) {
        table = $(this).val();
        id = $(this).attr("id");
        num = id.substring(6,id.length);
        getTableFields(num, table);
    });
    $("#field_"+n).change(function (event) {
        table = $("#table_" + n).val();
        field = $(this).val();
        getOperators(n, table, field);
    });
    $("#delete_clause_"+n).click(function (event) {
        $("#search_clause_"+n).remove();
    });
    $("#add_clause_"+(n+1)).click(function (event) {
        addSearchClause(n+1);
    });
}

function searchLink(link) {
    $("#loading_message").addClass("show").removeClass("hidden");
    document.location.href = link;
}