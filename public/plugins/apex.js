

function tbl_check_all(box, table_id) {
    $('#' + table_id + '_tbody td input:checkbox').attr('checked', box.checked);
}

function ajax_send(function_alias, data, field_names) {

    // Check if form exists
    if (!document.forms[0]) { 
        alert("Unable to send AJAX request, as no HTML form exists on this page");
        return false;
    }
    var form = document.forms[0];

    // Create request
    var request = '';
    var elements = field_names == '' ? form.elements : field_names.split(',');
    for (x=0; x < elements.length; x++) {
        var e = field_names == '' ? elements[x] : form[elements[x]];
        if (!e) { continue; }
        if (e.type == 'checkbox' && e.checked === false) { continue; }

        // Add to request
        request += e.name + '=' + encodeURIComponent(e.value) + '&';
    }
    request += data;

    // Send AJAX request
    var url = '/ajax/' + function_alias;
    $.post(url, request, function(data, status) { 
        ajax_response(data, status);
    });

}

function ajax_confirm(message, function_alias, data, field_names) {
    var response = confirm(message);
    if (response === true) { 
        ajax_send(function_alias, data, field_names);
    }
}


function ajax_response(res, status) { 

    // Check for error
    if (res.status && res.status == 'error') { 
        alert("Error: " + res.errmsg + "\n\nFile: " + res.file + "\nLine: " + res.line);
        return false;
    }

    // Go through actions
    for (x=0; x < res.actions.length; x++) { 
        if (!res.actions[x]) { continue; }
        var e = res.actions[x];

        // Alert
        if (e.action == 'alert') { 
            alert(e.message);

        // Prepend text to element
        } else if (e.action == 'prepend') { 
            var div = document.getElementById(e.divid);
            div.innerHTML = e.html + div.innerHTML;

        // Append
        } else if (e.action == 'append') { 
            document.getElementById(e.divid).innerHTML += e.html;

        // Set text
        } else if (e.action == 'set_text') { 
            document.getElementById(e.divid).innerHTML = e.text;

        // Set display
        } else if (e.action == 'set_display') { 
            document.getElementById(e.divid).style.display = e.display;

        // Clear list
        } else if (e.action == 'clear_list') { 
            $('#' + e.divid).empty();

        // Play sound
        } else if (e.action == 'play_sound') { 
            var audio = new Audio('/plugins/sounds/' + e.sound_file);
            audio.play();

        // Clear all table rows
        } else if (e.action == 'clear_table') { 
            $('#' + e.divid + ' > tbody').empty();

        // Remove checked table rows
        } else if (e.action == 'remove_checked_rows') { 
            $('#' + e.divid + ' tbody tr').has('input[type="checkbox"]:checked').remove()

        // Add data row
        } else if (e.action == 'add_data_row') { 

            // Add row
            var tbody = document.getElementById(e.divid + '_tbody');
            var row = tbody.insertRow(tbody.rows.length);

            // Go through cells
            for (y=0; y < e.cells.length; y++) { 
                var c = row.insertCell(y);
                c.innerHTML = e.cells[y];
            }

        }
    }


}


function open_modal(modal_alias, data) { 

    // Send AJAX request
    var url = '/modal/' + modal_alias;
    $.post(url, data, function(response, status) {

        // Check for error
        if (response.status && response.status == 'error') { 
            alert("Error: " + response.errmsg);
            return false;
        }

        // Open modal

        document.getElementById('apex_modal-title').innerHTML = response.title;
        document.getElementById('apex_modal-body').innerHTML = response.body;
        document.getElementById('apex_modal').style.zindex = 9999;
        document.getElementById('apex_modal').style.display = 'block';
        //$('#apex_modal').show();
    });

}

function close_modal() {
    $('#apex_modal').hide();
}
