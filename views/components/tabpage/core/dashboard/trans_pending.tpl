
<h3>Pending Deposits</h3>

<a:form action="admin/financial/pending">
<a:function alias="display_table" table="transaction:transaction" status="pending" controller="deposit">

<a:form_table><tr>
    <td>Status:</td>
    <td>
        <input type="radio" name="pending_deposit_status" value="approved" checked="checked"> Approved 
        <input type="radio" name="pending_deposit_status" value="declined"> Declined
    </td>
</tr>
    <a:ft_textarea name="pending_deposit_note" label="Optional Note">
    <a:ft_submit value="pending_deposit_process" label="Process Checked Deposits">
</a:form_table></form>



