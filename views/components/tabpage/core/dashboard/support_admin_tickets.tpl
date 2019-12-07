
<h3>My TicketsM.h3<

<h5>Public Tickets</h5>
<a:function alias="display_table" table="support:tickets" is_open="1" is_public="1" admin_id="~userid~" is_pending="0">

<h5>Member Tickets</h5>
<a:function alias="display_table" table="support:tickets" is_open="1" is_public="0" admin_id="~userid~" is_pending="0">


<h3>Unassigned Tickets</h3>

<h5>Public Tickets</h5>
<a:function alias="display_table" table="support:tickets" is_open="1" is_public="1" admin_id="0" is_pending="0">

<h5>Member Tickets</h5>
<a:function alias="display_table" table="support:tickets" is_open="1" is_public="0" admin_id="0" is_pending="0">




