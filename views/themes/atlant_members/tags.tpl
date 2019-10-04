
******************************
* This file contains all HTML snippets for the special 
* HTML tags that are used throughout Apex.  These are tags prefixed with "a:", such as 
* <a:box>, <a:form_table>, and others.
*
* Below are lines with the format "[[tag_name]]", and everything below that 
* line represents the contents of that HTML tag, until the next occurrence of "[[tag_name]]" is reached.
*
* Tag names that contain a period (".") signify a child item, as you will notice below.
*
******************************



********************
* <a:form_table> ... </a:form_table>
* <a:FORM_FIELD>
*
* The form table, and various form field elements
********************

[[form_table]]
<table border="0" class="form_table" style="width: ~width~; align: ~align~;">
    ~contents~
</table>


[[form_table.row]]
<tr>
    <td><label for="~name~">~label~:</label></td>
    <td><div class="form-group">
        ~form_field~
    </div></td>
</tr>


[[form_table.separator]]
<tr>
    <td colspan="2" style="padding: 5px 0px;"><h5>~label~</h5></td>
</tr>


[[form.submit]]
<button type="submit" name="submit" value="~value~" class="btn btn-primary btn-~size~">~label~</button>


[[form.reset]]
<button type="reset" class="btn btn-primary btn-md">Reset Form</button>


[[form.button]]
<a href="~href~" class="btn btn-prinary btn-~size~">~label~</a>


[[form.boolean]]
<input type="radio" name="~name~" class="form-control" value="1" ~chk_yes~> Yes 
<input type="radio" name="~name~" class="form-control" value="0" ~chk_no~> No 


[[form.select]]
<select name="~name~" class="form-control" ~width~ ~onchange~>
    ~options~
</select>


[[form.textbox]]
<input type="~type~" name="~name~" value="~value~" class="form-control" id="~id~" ~placeholder~ ~actions~ ~validation~ />


[[form.textarea]]
<textarea name="~name~" class="form-control" id="~id~" style="width: ~width~; height: ~height~;" ~placeholder~>~value~</textarea>


[[form.phone]]
<select name="~name~_country" class="form-control" style="width: 30px; float: left;">
    ~country_code_options~
</select> 
<input type="text" name="~name~" value="~value~" class="form-control" style="width: 170px; float: left;" ~placeholder~>


[[form.amount]]
<span style="float: left;">~currency_sign~</span> 
<input type="text" name="~name~" value="~value~" class="form-control" style="width: 60px; float: left;" ~placeholder~ data-parsley-type="decimal">


[[form.date]]
<select name="~name~_month" class="form-control" style="width: 120px; float: left;">
    ~month_options~
</select> 
<select name="~name~_day~" class="form-control" style="width: 30px; float: left;">
    ~day_options~
</select>, 
<select name="~name~_year~" class="form-control" style="width: 70px; float: left;">
    ~year_options~
</select>

[[form.time]]
<select name="~name~_hour" class="form-control" style="width: 60px; float: left;">
    ~hour_options~
</select> : 
<select name="~name~_min" class="form-control" style="width: 60px; float: left;">
    ~minute_options~
</select>


[[form.date_interval]]
<input type="text" name="~name~_num" class="form-control" value="~num~" style="width: 30px; float: left;"> 
<select name="~name~_period" class="form-control" style="width: 80px; float: left;">
    ~period_options~
</select>




********************
* <a:box> ... </a:box>
* <a:box_header title="..."> ... </a:box_header>
*
* Containers / panels that help separate different sections of the page.  Can optionally 
* contain a header with title.
********************

[[box]]
<div class="panel panel-primary">
    ~box_header~
    <div class="panel-body">
        ~contents~
    </div>
</div>

[[box.header]]
<span style="border-bottom: 1px solid #333333; margin-bottom: 8px;">
    <div class="panel-heading">
        <h3>~title~</h3>
    </div>
    ~contents~
</span>


********************
* <a:input_box> ... </a:input_box>
*
* Meant for a full width sizeh, short sized bar.  Used 
* for things such as a search textbox, or other bars to separate them from 
* the rest of the page content.
*
* Example of this is Users->Manage User menu of the administration 
* panel, where the search box is surrounded by an input box.
********************

[[input_box]]
<div style="background: #cecece; width: 95%; margin: 12px; padding: 20px; font-size: 12pt; color: #eee;">
    ~contents~
</div>



********************
* <a:callouts>
*
* The callouts / informational messages that are displayed on the 
* top of pages after an action is performed.  These messages are 
* for: success, error, warning, info. 
*
* The first element is the HTML code of the callouts itself, the second 
* and third elements are JSON encoded strings that define the 
* CSS and icon aliases to use for each message type.
********************

[[callouts]]
<div class="callout callout-~css_alias~ text-center"><p>
    <i class="icon ~icon~"></i> ';
    ~messages~
</p></div>


[[callouts.css]]
[
    "success": "success", 
    "error": "danger", 
    "warning": "warning", 
    "info": "info"
]


[[callouts.icon]]
[
    "success": "fa fa-check", 
    "error": "fa fa-ban", 
    "warning": "fa fa-warning", 
    "info": "fa fa-info"
]


********************
* <a:nav_menu>
*
* The navigation menu of the theme, including header / separator 
* items, parent menus, and submenus.
********************

[[nav.header]]
<li class="xn-title">~name~</li>                    


[[nav.parent]]
<li class="xn-openable">
    <a href="#">~icon~ <span class="xn-text">~name~</span></a>
    <ul>
        ~submenus~
    </ul>
</li>


[[nav.menu]]
<li><a href="~url~"><span class="xn-text">~name~</span></a></li>




********************
* <a:tab_control> ... </a:tab_control>
* <a:tab_page name="..."> ... </a:tab_page>
*
* The tab controls.  Includes the tab control itself, nav items and 
* the body pane of tab pages.
********************

[[tab_control]]
<div class="panel panel-default tabs">
    <ul class="nav nav-tabs">
        ~nav_items~
    </ul>


<div class="panel-body tab-content">
    ~tab_pages~
</div>
</div>


[[tab_control.nav_item]]
<li class="~active~">
<a href="#tab~tab_num~" role="tab" data-toggle="tab" aria-expanded="true">~name~</a>
</li>                            



[[tab_control.page]]
<!-- ***************   Please enter condition: if == tab1 (active) ****************** -->
<div class="tab-pane active" id="tab~tab_num~">
 ~contents~                           
</div>



[[tab_control.css_active]]
icon active



********************
* <a:data_table> ... </a:data_table>
* <a:table_search_bar>
* <a:th> ... <a:th>
* <a:tr> ... </a:tr>
*
* The data tables used throughout the software.
********************

[[data_table]]
<table class="table datatable">
    <thead>
    ~search_bar~

    <tr>
        ~header_cells~
    </tr>
    </thead>

    <tbody id="~table_id~_tbody">
        ~table_body~
    </tbody>

    <tfoot><tr>
        <td colspan="~total_columns~" align="right">
            ~delete_button~
            ~pagination~
        </td>
    </tr></tfoot>
</table>


[[data_table.th]]
<th>~sort_asc~ ~name~ ~sort_desc~</th>


[[data_table.sort_asc]]
<a href="javascript:ajax_send('core/sort_table', '~ajax_data~&sort_col=~col_alias~&sort_dir=asc', 'none');" border="0" title="Sort Ascending ~col_alias~">
    <i class="fa fa-sort-asc"></i>
</a>


[[data_table.sort_desc]]
<a href="javascript:ajax_send('core/sort_table', '~ajax_data~&sort_col=~col_alias~&sort_dir=desc', 'none');" border="0" title="Sort Decending ~col_alias~">
    <i class="fa fa-sort-desc"></i>
</a>


[[data_table.search_bar]]
<tr>
    <td colspan="~total_columns~" align="right">
        <i class="fa fa-search"></i> 
        <input type="text" name="search_~table_id~" placeholder="~search_label~..." class="form-control" style="width: 210px;"> 
        <a href="javascript:ajax_send('core/search_table', '~ajax_data~', 'search_~table_id~');" class="btn btn-primary btn-md">~search_label~</a>
    </td>
</tr>


[[data_table.delete_button]]
<a href=\"javascript:ajax_confirm('Are you sure you want to delete the checked records?', 'core/delete_rows', '~ajax_data~', '~form_name~');" class="btn btn-primary btn-md" style="float: left;">~delete_button_label~</a>



********************
* <a:pagination>
*
* Pagination links, generally displayed at the bottom of 
* data tables, but can be used anywhere.
********************

[[pagination]]
<span id="pgnsummary_~id~" style="vertical-align: middle; font-size: 8pt; margin-right: 7px;">
    <b>~start_record~ - ~end_record~</b> of <b>~total_records~</b>
</span>

<ul class="pagination" id ="pgn_~id~">
    ~items~
</ul>


[[pageination.item]]
<li style="display: ~display~;"><a href="~url~">~name~</a></li>

[[pagination.active_item]]
<li class="active"><a>~page~</a></li>



********************
* <a:dropdown_alerts>
* <a:dropdown_messages>
*
* The list items used for the two drop down lists, notifications / alerts and 
* messages.  These are generally displayed in the top right corner 
* of admin panel / member area themes.
********************


[[dropdown.alert]]
<a class="list-group-item" href="~url~">
    <strong>~message~</strong>
    <small class="text-muted">~time~</small>
</a>








[[dropdown.message]]
<a href="~url~" class="list-group-item">
    <span class="contacts-title">~from~</span>
    <p>~message~</p>
</a>


********************
* <a:boxlists>
*
* The boxlists as seen on pages such as Settings->Users.  Used to 
* display links to multiple pages with descriptions.
********************

[[boxlist]]
<ul class="boxlist">
    ~items~
</ul>



[[boxlist.item]]
<li><p><a href="~url~">
    <b>~title~</b><br />
    ~description~
</p></li>


********************
* dashboard
*
* HTML snippets for the dashboard widgets.
********************

[[dashboard]]
<div class="row">
    ~top_items~
</div>

<div class="row">
    <div class="col-md-9">
        ~tabcontrol~
    </div>

    <div class="col-md-3">
        ~right_items~
    </div>
</div>


[[dashboard.top_item]]
<div class="col-md-3">
    <div class="widget widget-default widget-item-icon">
        <div class="widget-item-left">
            <span class="~panel_class~"></span>
        </div>                             
        <div class="widget-data">
            <div class="widget-int num-count">~contents~</div>
            <div class="widget-title">~title~</div>
        </div>      
        <div class="widget-controls">                                
            <a href="#" class="widget-control-right widget-remove" data-toggle="tooltip" data-placement="top" title="Remove Widget"><span class="fa fa-times"></span></a>
        </div>
    </div>
</div>

[[dashboard.right_item]]
<div class="card">
    <div class="card-header bg-transparent header-elements-inline">
        <span class="card-title font-weight-semibold">~title~</span>
    </div>
    <div class="card-body">
        <ul class="media-list">
            <li class="media">
                <div class="media-body">
                    ~contents~
                </div>
            </li>
        </ul>
    </div>
</div>





