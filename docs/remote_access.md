
# Remote Access Client

Apex contains built-in support for remote access, allowing a system to be remotely updated instantly from another Apex installation without requiring access to the 
server.  This is useful for designers / developers who are working on a remote Apex installation, but have a desire to easily upload modified files to the system from their own 
local Apex installation.  This guide explains how to setup remote access, and the 
various CLI commands available.

Within this guide the remote server is the Apex installation that will be automatically updated, and the local server will be the Apex installation 
the designer / developer is modifying, and wishes to upload to the remote server from.

1. <a href="#setup">Setup Remote Access</a>
2. <a href="#cli_commands">CLI Commands</a>
    1. <a href="#remote_copy">remote_copy</a>
    2. <a href="#remote_rm">remote_rm</a>
    3. <a href="#remote_save">remote_save</a>
    4. <a href="#remote_delete">remote_delete</a>
    5. <a href="#remote_sql">remote_sql</a>
    6. <a href="#remove_scan">remote_scan</a>


<a name="setup"></a>
## Setup Remote Access

By default remote access is disabled, but you may easily enable it.  Login to the administration panel 
of the remote server, visit the Settings-&gt;General menu, and top of the API Keys tab you will see a section for remote access.  Change the radio button to enable it, and update the settings.  Then also 
hit the button to regenerate the API key.  Please ensure to copy and save this API key, as you will only be able to read it once.

Next, within the local server open terminal, change to the Apex installation directory, and run the command: `./apex update_remote_apikey`.  This will prompt you for the 
full installation URL of the remote server (eg. https://example.com), and the API key.  Enter both, and that's it!  Remote access is now enabled, and ready to go.


<a name="cli_commands"></a>
## CLI Commands

All remote access functionality is available via CLI commands from the local server, which are explained below.  Please note, depending on 
server permissions, all updates to the remote server may take a couple minutes as they will have to wait for the next crontab job to run again.



<a name="remote_copy"></a>
#### `remote_copy [FILE1] [FILE2] [FILE3]...`

**Description:** Copies files from the local server to the remote server, and can optionally accept multiple filenames delimited by a space.  The filenames must be relative to the Apex installation directory.

**Examples**

~~~
apex remote_copy src/mypackage/somelib.php
apex remote_copy views/admin/some_menu/myview.tpl views/tpl/public/home_page.tpl
~~~


<a name="remote_rm"></a>
#### `remote_rm [FILE1] [FILE2] [FILE3] ...`

**Description:** Deletes files / directories from the remote server, and can contain multiple files delimited by a space.  All filenames must be relative to the installation directory.

**Example**

~~~
apex remote_rm src/mypackage/somelib.php
apex remote_rm views/admin/some_menu/myview.tpl views/tpl/public/home_page.tpl
~~~


<a name="remote_save"></a>
#### `remote_save TYPE PACKAGE:[PARENT:]ALIAS`

**Description:** Saves a component to the remote server, regardless if it's being created for the first time or only the code files are saved.  This is similar to the standard `apex create` CLI command to create a component, except if the component already exists all code files associated with it will be saved on the remote server.

**Examples**

~~~
apex remote_save view admin/settings/mypackage
apex remote_save table mypackage:some_table
~~~


<a name="remote_delete"></a>
#### `remote_delete TYPE PACKAGE:[PARENT:]ALIAS`

**Description:** Deletes a component from the remote server, including all code files associated with it.

**Example**

~~~
apex remote_delete view admin/settings/mypackage
apex remote_delete table mypackage:some_table
~~~


<a name="remote_sql"></a>
#### `remote_sql SQL`

**Description:** Executes the given SQL statement against the remote database.  If it's a SELECT statement, returns the results in JSON format.

**Example**

~~~
apex remote_sql SELECT username,email FROM users ORDER BY id
apex remote_sql INSERT INTO mypackage_test (amount, name) VALUES (15, 'Test Product')
~~~


<a name="remote_scan"></a>
#### `remote_scan PACKAGE_ALIAS`

**Description:** An alias of the `apex scan` CLI command, except this scans a package on the remote server.  This will also save the current contents of the /etc/PACKAGE_ALIAS/package.php file, and save it accordingly to the remote server.

**Example:** `apex remote_scan mypackage`



