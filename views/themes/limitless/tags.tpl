
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
    <td colspan="2"><h5>~label~</h5></td>
</tr>


[[form.submit]]
<div class="text-left">
    <button type="submit" name="submit" value="~value~" class="btn btn-primary btn-~size~">~label~</button>
</div>

[[form.reset]]
<!-- <button type="reset" class="btn btn-primary btn-md">Reset Form</button> -->


[[form.button]]
<a href="~href~" class="btn btn-primary btn-~size~">~label~</a>


[[form.boolean]]
<div class="radioform">
    <input type="radio" name="~name~" class="form-control" value="1" ~chk_yes~ /> <span>Yes</span> 
    <input type="radio" name="~name~" class="form-control" value="0" ~chk_no~ /> <span>No</span> 
</div>

[[form.select]]
<select name="~name~" class="form-control" ~width~ ~onchange~>
    ~options~
</select>


[[form.textbox]]
<input type="~type~" name="~name~" value="~value~" class="form-control" id="~id~" ~placeholder~ ~actions~ ~validation~ />



[[form.textarea]]
<textarea name="~name~" class="form-control" id="~id~" style="width: 100%" ~placeholder~>~value~</textarea>


[[form.phone]]
<div class="form-group">
    <select name="~name~_country" class="form-control col-lg-2">
        ~country_code_options~
    </select> 
    <input type="text" name="~name~" value="~value~" class="form-control col-lg-10"  ~placeholder~>
</div>

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
<div class="form-group">
    <div class="col-lg-8" style="padding-left: 0">
        <input type="text" name="~name~_num" class="form-control" value="~num~" > 
    </div>
    <div class="col-lg-4" style="padding-right: 0">
        <select name="~name~_period" class="form-control" style="width: 100%" >
            ~period_options~
        </select>
    </div>
</div>




********************
* <a:box> ... </a:box>
* <a:box_header title="..."> ... </a:box_header>
*
* Containers / panels that help separate different sections of the page.  Can optionally 
* contain a header with title.
********************

[[box]]
<div class="panel panel-default">
    <div class="panel-heading"> ~box_header~</div>
    <div class="panel-body">
        ~contents~
    </div>
</div>

[[box.header]]
<span style="border-bottom: 1px solid #333333; margin-bottom: 8px;">
    <h3>~title~</h3>
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
<div class="panel panel-default search_user">
    <div class="panel-body">
        ~contents~
    </div>
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
<div class="callout callout-~css_alias~ text-center success"><p>
    <i class="icon ~icon~"></i>
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
<li class="nav-item-header"><div class="text-uppercase font-size-xs line-height-xs">~name~</div> <i class="icon-menu" title="~name~"></i></li>


[[nav.parent]]
<li class="nav-item nav-item-submenu">
    <a href="~url~" class="nav-link">~icon~ <span>~name~</span></a>
    <ul class="nav nav-group-sub" data-submenu-title="~name~">
        ~submenus~
    </ul>
</li>


[[nav.menu]]
<li class="nav-item"><a href="~url~" class="nav-link">~icon~~name~</a></li>


********************
* <a:tab_control> ... </a:tab_control>
* <a:tab_page name="..."> ... </a:tab_page>
*
* The tab controls.  Includes the tab control itself, nav items and 
* the body pane of tab pages.
********************

[[tab_control]]

<div class="nav-tabs-custom">
    <ul class="nav nav-tabs">
        ~nav_items~
    </ul>

    <div class="tab-content">
        ~tab_pages~
    </div>
</div>



[[tab_control.nav_item]]
<li class="~active~"><a href="#tab~tab_num~" data-toggle="tab">~name~</a></li>


[[tab_control.page]]
<div class="tab-pane ~active~" id="tab~tab_num~">
    ~contents~
</div>


[[tab_control.css_active]]
active



********************
* <a:data_table> ... </a:data_table>
* <a:table_search_bar>
* <a:th> ... <a:th>
* <a:tr> ... </a:tr>
*
* The data tables used throughout the software.
********************

[[data_table]]
<table class="table table-bordered table-striped table-hover" id="~table_id~">
    <thead>
        ~search_bar~
    <tr>
        ~header_cells~
    </tr>
    </thead>

    <tbody id="~table_id~_tbody" class="bodytable">
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
<th class="boxheader"> <span>~name~</span> ~sort_desc~ ~sort_asc~</th>


[[data_table.sort_asc]]
<a href="javascript:ajax_send('core/sort_table', '~ajax_data~&sort_col=~col_alias~&sort_dir=asc', 'none');" border="0" title="Sort Ascending ~col_alias~" class="asc">
    <i class="fa fa-sort-asc"></i>
</a>


[[data_table.sort_desc]]
<a href="javascript:ajax_send('core/sort_table', '~ajax_data~&sort_col=~col_alias~&sort_dir=desc', 'none');" border="0" title="Sort Decending ~col_alias~" class="desc">
    <i class="fa fa-sort-desc"></i>
</a>


[[data_table.search_bar]]
<tr>
    <td style="border-top:1px solid #ccc" colspan="~total_columns~" align="right">
        <div class="formsearch">
            <input type="text" name="search_~table_id~" placeholder="~search_label~..." class="form-control" style="width: 210px;"> 
            <a href="javascript:ajax_send('core/search_table', '~ajax_data~', 'search_~table_id~');" class="btn btn-primary btn-md"><i class="fa fa-search"></i></a>
        </div>
    </td>
</tr>


[[data_table.delete_button]]
<a href="javascript:ajax_confirm('Are you sure you want to delete the checked records?', 'core/delete_rows', '~ajax_data~', '');" class="btn btn-primary btn-md botontest" style="float: left;">~delete_button_label~</a>



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
<li class="media">
    <div class="media-body">
        <a href="~url~">~message~
        <div class="text-muted font-size-sm">~time~</div>
	</a>
    </div>
</li>


[[dropdown.message]]
<li class="media">
    <div class="media-body">

        <div class="media-title">
            <a href="~url~">
                <span class="font-weight-semibold">~from~</span>
                <span class="text-muted float-right font-size-sm">~time~</span>
            </a>
        </div>

        <span class="text-muted">~message~</span>
    </div>
</li>



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
<li>
    <a href="~url~">
        <b>~title~</b><br />
        ~description~
    </a>
</li>


********************
* dashboard
*
* HTML snippets for the dashboard widgets.
********************

[[dashboard]]

<div class="row boxgraf">
    ~top_items~
</div>

<div class="panel panel-flat">
    ~tabcontrol~
</div>

<div class="sidebar sidebar-light bg-transparent sidebar-component sidebar-component-right border-0 shadow-0 order-1 order-md-2 sidebar-expand-md">
    <div class="sidebar-content">
        ~right_items~
    </div>
</div>

[[dashboard.top_item]]
<div class="col-lg-4">
    <div class="~panel_class~">
        <div class="panel-body">

            <h3 class="no-margin">3,450</h3>
                ~title~
            </div>
            <div class="text-muted text-size-small">~contents~</div>
        </div>
        <div class="container-fluid">
            <div id="~divid~"></div>
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




