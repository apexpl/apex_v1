
# Views - HTML Forms (`<a:form_table>`)

Apex supports special HTML tags allowing form tables to be quickly designed, and produces the 
same result as the [HTML Form component](components/form.md) except are manually written into the .tpl files.  Below explains 
the HTML tags used for forms.


### `<a:form_table> ... </a:form_table>`

**Description:** Creates a simple table with default width of 80%, and necessary cellpadding.  Should always
be used when manually putting in a form or a simple two column text based table.  Requires a closing tab, and
uses custom CSS class of "form_table" which should be added to every theme.

**Attributes**

Attribute | Required | Description 
------------- |------------- |------------- 
width | No | Width of the table, defaults to 80%. 
align | No | Alignment of the table, defaults to "left".


### `<a:ft_seperator>`

**Description:** Generates a two column width row, with indented and bolded text.  Used to separate sets of
form fields.

Attribute | Required | Description 
------------- |------------- |------------- 
label | Yes | The text of the seperator.


### `<a:ft_FIELD> / <a:FIELD>` Tags

Every form field is available via both the `<a:ft_FIELD>` and `<a:FIELD>` tags.  Within the `<a:form_table>
... </a:form_table>` tags, you can place the `<a:ft_FIELD>` tags, which are replaced with a two column table
row, the left column being the label of the form field, and the right column being the form field itself.
Alternatively, you can also use the `<a:FIELD>` tags, which are only replaced with the form field itself, and
no two column table row.


Tag | Description 
------------- |------------- 
`<a:ft_textbox> / <a:textbox>` | Input textbox field.
`<a:ft_textarea> / <a:textarea>` | Input textarea form field. 
`<a:ft_phone> / <a:phone>` | Phone number. Contains a small select list for the country count, and a text field for the phone number. 
`<a:ft_date / <a:date>` | Date.  Consists of three select lists for the month, day, and year. 
`<a:ft_date_interval> / <a:date_interval>` | Date interval.  One small textbox for an integer, and a small select list for the interval (days, weeks, months, years) 
`>a:ft_select> / <a:select>` | Select list. 
`<a:ft_boolean> / <a:boolean>` | Boolean.  Two radio buttons, Yes / No. 
`<a:ft_custom>` | Custom row.
`<a:ft_blank>` | Blank row, colspan of 2. 
`>a:ft_submit> / <a:submit>` | Submit button.


### Example

~~~html
<a:form_table>
    <a:ft_seperator label="Contact Details">
    <a:ft_textbox name="full_name">
    <a:ft_textbox name="email" label="E-Mail Address" required="1" datatype="email">
    <a:ft_select name="status" data_source="hash:users:status" required="1">
    <a:ft_submit value="add" label="Add New Contact">
</a:form_table>
~~~


