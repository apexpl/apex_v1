
# Global Functions

There are a handful of global functions available, which help facilitate development within Apex.  All
functions are available within the */src/app/sys/functions.php* library, and are explained below.


Method | Description
------------- |------------- 
tr($message, $args) | Used to format messages to be sent for output to the web browser.  This does both, replaces placeholders as needed, and if necessary translates the message into another language.  Supports both, sequential and name based place holders.
fdate($date, bool $add_time) | Formats a date into the proper, readable format for the web browser, plus also changes it to the correct timezone.  All dates within Apex are stored in UTC, this function will check the authenticated user's preferred timezone, and update the time as necessary to display the time and date in the correct timezone.  Should always be used when displaying any date / time within the web browser.
fmoney($amount, $currency, $include_abbr) | Formats a decimal into an amount using the proper currency symbol and decimals.  Always use this function when displaying an amount.
exchange_money(float $amount, string $from_currency, string $to_currency) | Exchanges the amount specified from the one currency, to the specified currency using the latest exchange rates in the database.
check_package($alias) | Checks whether or not a package alias is installed on the system, and returns a boolean. Useful when developing packages that act / display things differently depending on whether or not a certain package is installed.
ftype($value, $type) | Formats the data type of a value as desired, as issues were found using PHP's built-in setType() function.



