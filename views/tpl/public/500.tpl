
<h1>Error</h1>

<div class="row"><div class="col-md-12">
    <p><b>~err_message~</b></p><br />

    <a:if '~config.core:mode~' == 'devel'>
        <p><i>
            File: ~err_file~<br />
            Line: ~err_line~
        </i></p><br />
    </a:if>

</div></div><br />

<a:if '~config.core:mode~' == 'devel'>
    <h3>Debug Information</h3>

    <a:function alias="display_tabcontrol" tabcontrol="core:debugger">
</a:if>



</blockquote><br />



