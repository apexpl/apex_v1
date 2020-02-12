
# Import / Export Data Between Packages

At times you may wish to include specific data from your package when another package is compiled / published.  This is useful 
when users are creating a site specific package, and for example, you want to ensure all blog posts they created within the "blog" package are 
included when they compile / publish the site specific package.  This page serves to explain that process within Apex.


## Export Data

When a package is compiled, the "core.packages.compile" RPC event message is dispatched, allowing you to create a 
listener / worker for the message, and return any desired data specific to your package.  For example, if you're developing a package named "blog", you would create a 
worker / listener within terminal with something like:

`./apex create worker blog:packages core.packages`

This will create a new PHP class at */src/blog/worker/packages.php*, and within this class you need to define 
one method named "compile".  This method needs to return one associative array, the keys being filenames as defined by you, and the values 
being the full path on the server to each file (ie. /tmp/ files).  For example:


~~~php
public function compile(event $msg)
{

    // Get package being compiled
    $pkg_alias = $msg->get_params();

    // Gather some data assigned to $pkg_alias package, maybe a JSON array or whatever is best.
    $data = '';

    // Save tmp file
    $tmp_file = tempnam(sys_get_temp_dir(), 'apex');
    file_put_contents($tmp_file, $data);

    // Set results
    $files = [
        'data.json' => $tmp_file
    ];

    // Return
    return $files;

}
~~~

The above example will grab whatever data necessary for the package being compiled, save it in a 
temporary file, and return an associative array with the filename we would like and the full path to 
the temporary file.


## Import Data

When a package is installed, if it contains additional data from your package, you will want to import this.  For example, 
if you're developing a "blog" package and your package exported a bunch of blog posts that were assigned to a site specific package, upon 
installing that site specific package again you will also want to import those blog posts.

This is done by modifying the */etc/PACKAGE/package.php* configuration file, and adding a *import_data()* method.  This 
method will accept one associative array being the same as the one returned during the export of data.  The keys will be the filename, and the 
values the full path on the server to that file.  It's then up to you to parse and import that data accordingly.  For example:

~~~php

public function import_data(string $pkg_alias, array $files)
{

    // Get data JSON
    $data = file_get_contents($files['data.json']);

    // Import the data, however we see fit.

}
~~~

That's all ther is to it, if you ever need to ensure user created data within your package is migrated properly when 
site specific packages are created / installed.




 

