
# Auto-Suggest / Complete Component

&nbsp; | &nbsp;
------------- |-------------
**Description:** | Standard auto-suggest/complete boxes where users enter a few characters, and a list of suggestions is automatically presented to them in a list to choose from.  Useful for things such as searching for a specific user.  No Javascript required, and only minutes to easily implement.
**Create Command:** | `php apex.php create autosuggest PACKAGE:ALIAS`
**File Location:** | /src/PACKAGE/autosuggest/ALIAS.php
**Namespace:** | `apex\PACKAGE\autosuggest`
**HTML Tag:** | `<e:function alias="display_autosuggest" autosuggest="PACKAGE:ALIAS">`

## Methods

Below explains all methods found within this class.


### `array search($string $term)`

Performs the necessary search using the search term passed in `$term`.  Simply returns an array of key-value pairs where the keys are the unique ID# of the option, and the values are the name the user sees within the browser.

Upon form submission, the ID# of the selected option will appear in the POST variable denoted by the `name="..."` attribute used within the `<e:function>` tag for the auto-suggest suffixed by "-id".  For example, if the name attribute is "user", then the ID# of the selected user will be found at `registry::$post['user_id']`.


