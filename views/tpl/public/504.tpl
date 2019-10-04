
<h1>Oops!  Something went wrong...</h1>

<p>We're sorry, a temporary issue occurred while processing your request.  Details of the issue have been logged, and our technical team has been notified.  Please try again shortly.</p>

<a:if '~config.core:mode~' == 'devel'>
    <p><b>Developer Note:</b></p> You are most likely receiving this error because either the internal RPC or Web Socket server 
    is not currently running.  Please restart the Apex daemons with "/src/apex restart" init script.</p>
</a:if>


