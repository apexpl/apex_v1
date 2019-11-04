<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\redis;
use apex\svc\components;
use apex\app\exceptions\ComponentException;
use apex\app\pkg\package_config;
use apex\svc\date;
use apex\svc\io;


/**
 * Handles creating, adding, deleting, and updating components within 
 * packages.  Used for all package / upgrade functions. 
 */
class pkg_component
{

    // Set code templates
    private static $code_templates = array(
        'ajax' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxhamF4OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xkZWJ1ZzsKdXNlIGFwZXhcYXBwXHdlYlxhamF4OwoKLyoqCiAqIEhhbmRsZXMgdGhlIEFKQVggZnVuY3Rpb24gb2YgdGhpcyBjbGFzcywgYWxsb3dpbmcgCiAqIERPTSBlbGVtZW50cyB3aXRoaW4gdGhlIGJyb3dzZXIgdG8gYmUgbW9kaWZpZWQgaW4gcmVhbC10aW1lIAogKiB3aXRob3V0IGhhdmluZyB0byByZWZyZXNoIHRoZSBicm93c2VyLgogKi8KY2xhc3MgfmFsaWFzfiBleHRlbmRzIGFqYXgKewoKLyoqCiAgICAqIFByb2Nlc3NlcyB0aGUgQUpBWCBmdW5jdGlvbi4KICoKICogUHJvY2Vzc2VzIHRoZSBBSkFYIGZ1bmN0aW9uLCBhbmQgdXNlcyB0aGUgCiAqIG1vZXRoZHMgd2l0aGluIHRoZSAnYXBleFxhamF4JyBjbGFzcyB0byBtb2RpZnkgdGhlIAogKiBET00gZWxlbWVudHMgd2l0aGluIHRoZSB3ZWIgYnJvd3Nlci4gIFNlZSAKICogZG9jdW1lbnRhdGlvbiBmb3IgZHVydGhlciBkZXRhaWxzLgogKi8KcHVibGljIGZ1bmN0aW9uIHByb2Nlc3MoKQp7CgogICAgLy8gUGVyZm9ybSBuZWNlc3NhcnkgYWN0aW9ucwoKfQoKfQoK', 
        'autosuggest' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxhdXRvc3VnZ2VzdDsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcc3ZjXGRiOwp1c2UgYXBleFxzdmNcZGVidWc7CgoKLyoqCiAqIFRoZSBhdXRvLXN1Z2dlc3QgY2xhc3MgdGhhdCBhbGxvd3MgZGlzcGxheWluZyBvZiAKICogZHJvcCBkb3duIGxpc3RzIHRoYXQgYXJlIGF1dG9tYXRpY2FsbHkgZmlsbGVkIHdpdGggc3VnZ2VzdGlvbnMuCiAqLwpjbGFzcyB+YWxpYXN+CnsKCi8qKgogICAgKiBTZWFyY2ggYW5kIGRldGVybWluZSBzdWdnZXN0aW9ucy4KICoKICogU2VhcmNoZXMgZGF0YWJhc2UgdXNpbmcgdGhlIGdpdmVuICR0ZXJtLCBhbmQgcmV0dXJucyBhcnJheSBvZiByZXN1bHRzLCB3aGljaCAKICogYXJlIHRoZW4gZGlzcGxheWVkIHdpdGhpbiB0aGUgYXV0by1zdWdnZXN0IC8gY29tcGxldGUgYm94LgogKgogKiAgICAgQHBhcmFtIHN0cmluZyAkdGVybSBUaGUgc2VhcmNoIHRlcm0gZW50ZXJlZCBpbnRvIHRoZSB0ZXh0Ym94LgogKiAgICAgQHJldHVybiBhcnJheSBBbiBhcnJheSBvZiBrZXktdmFsdWUgcGFyaXMsIGtleXMgYXJlIHRoZSB1bmlxdWUgSUQjIG9mIHRoZSByZWNvcmQsIGFuZCB2YWx1ZXMgYXJlIGRpc3BsYXllZCBpbiB0aGUgYXV0by1zdWdnZXN0IGxpc3QuCiAqLwpwdWJsaWMgZnVuY3Rpb24gc2VhcmNoKHN0cmluZyAkdGVybSk6YXJyYXkgCnsKCiAgICAvLyBHZXQgb3B0aW9ucwogICAgJG9wdGlvbnMgPSBhcnJheSgpOwoKCiAgICAvLyBSZXR1cm4KICAgIHJldHVybiAkb3B0aW9uczsKCn0KCn0KCg==', 
        'cli' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxjbGk7Cgp1c2UgYXBleFxhcHA7CnVzZSBhcGV4XHN2Y1xkYjsKdXNlIGFwZXhcc3ZjXGRlYnVnOwoKLyoqCiAqIENsYXNzIHRvIGhhbmRsZSB0aGUgY3VzdG9tIENMSSBjb21tYW5kIAogKiBhcyBuZWNlc3NhcnkuCiAqLwpjbGFzcyB+YWxpYXN+CnsKCi8qKgogKiBFeGVjdXRlcyB0aGUgQ0xJIGNvbW1hbmQuCiAqICAKICogICAgIEBwYXJhbSBpdGVyYWJsZSAkYXJncyBUaGUgYXJndW1lbnRzIHBhc3NlZCB0byB0aGUgY29tbWFuZCBjbGluZS4KICovCnB1YmxpYyBmdW5jdGlvbiBwcm9jZXNzKC4uLiRhcmdzKQp7CgoKCn0KCn0KCg==', 
        'controller' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxjb250cm9sbGVyXH5wYXJlbnR+OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xkZWJ1ZzsKdXNlIGFwZXhcfnBhY2thZ2V+XGNvbnRyb2xsZXJcfnBhcmVudH47CgoKLyoqCiAqIENoaWxkIGNvbnRyb2xsZXIgY2xhc3MsIHdoaWNoIHBlcmZvcm1zIHRoZSBuZWNlc3NhcnkgCiAqIGFjdGlvbnMgLyBtZXRob2RzIGRlcGVuZGluZyBvbiB0aGUgc3RydWN0dXJlIG9mIHRoZSAKICogcGFyZW50IGNvbnRyb2xsZXIuCiAqLwpjbGFzcyB+YWxpYXN+IGltcGxlbWVudHMgfnBhcmVudH4KewoKLyoqCiAqIEJsYW5rIFBIUCBjbGFzcyBmb3IgdGhlIGNvbnRyb2xsZXIuICBGb3IgdGhlIAogKiBjb3JyZWN0IG1ldGhvZHMgYW5kIHByb3BlcnRpZXMgZm9yIHRoaXMgY2xhc3MsIHBsZWFzZSAKICogcmV2aWV3IHRoZSBhYnN0cmFjdCBjbGFzcyBsb2NhdGVkIGF0OgogKiAgICAgL3NyYy9+cGFja2FnZX4vY29udHJvbGxlci9+cGFja2FnZX4ucGhwCiAqCiAqLwoKCn0KCg==', 
        'controller_parent' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxjb250cm9sbGVyOwoKLyoqCiAqIFRoZSBwYXJlbnQgaW50ZXJmYWNlIGZvciB0aGUgY29udHJvbGxlciwgYWxsb3dpbmcgeW91IHRvIAogKiBkZWZpbmUgd2hpY2ggbWV0aG9kcyBhcmUgcmVxdWlyZWQgd2l0aGluIGFsbCBjaGlsZCBjb250cm9sbGVycy4KICovCmludGVyZmFjZSB+YWxpYXN+CnsKCgoKfQoK', 
        'cron' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxjcm9uOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xkZWJ1ZzsKCi8qKgogKiBDbGFzcyB0aGF0IGFuZGxlcyB0aGUgY3JvbnRhYiBqb2IuCiAqLwpjbGFzcyB+YWxpYXN+CnsKCiAgICAvLyBQcm9wZXJ0aWVzCiAgICBwdWJsaWMgJHRpbWVfaW50ZXJ2YWwgPSAnSTMwJzsKICAgIHB1YmxpYyAkbmFtZSA9ICd+YWxpYXN+JzsKCi8qKgogKiBQcm9jZXNzZXMgdGhlIGNyb250YWIgam9iLgogKi8KcHVibGljIGZ1bmN0aW9uIHByb2Nlc3MoKQp7CgoKCn0KCn0K', 
        'form' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxmb3JtOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xkZWJ1ZzsKCgovKiogCiAqIENsYXNzIGZvciB0aGUgSFRNTCBmb3JtIHRvIGVhc2lseSBkZXNpZ24gZnVsbHkgZnVuY3Rpb25hbCAKICogZm9ybXMgd2l0aCBib3RoLCBKYXZhc2NyaXB0IGFuZCBzZXJ2ZXItc2lkZSB2YWxpZGF0aW9uLgogKi8KY2xhc3MgfmFsaWFzfgp7CgogICAgLy8gUHJvcGVydGllcwogICAgcHVibGljICRhbGxvd19wb3N0X3ZhbHVlcyA9IDE7CgovKioKICogRGVmaW5lcyB0aGUgZm9ybSBmaWVsZHMgaW5jbHVkZWQgd2l0aGluIHRoZSBIVE1MIGZvcm0uCiAqIAogKiAgIEBwYXJhbSBhcnJheSAkZGF0YSBBbiBhcnJheSBvZiBhbGwgYXR0cmlidXRlcyBzcGVjaWZpZWQgd2l0aGluIHRoZSBlOmZ1bmN0aW9uIHRhZyB0aGF0IGNhbGxlZCB0aGUgZm9ybS4gCiAqCiAqICAgQHJldHVybiBhcnJheSBLZXlzIG9mIHRoZSBhcnJheSBhcmUgdGhlIG5hbWVzIG9mIHRoZSBmb3JtIGZpZWxkcy4gIFZhbHVlcyBvZiB0aGUgYXJyYXkgYXJlIGFycmF5cyB0aGF0IHNwZWNpZnkgdGhlIGF0dHJpYnV0ZXMgb2YgdGhlIGZvcm0gZmllbGQuICBSZWZlciB0byBkb2N1bWVudGF0aW9uIGZvciBkZXRhaWxzLgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9maWVsZHMoYXJyYXkgJGRhdGEgPSBhcnJheSgpKTphcnJheQp7CgogICAgLy8gU2V0IGZvcm0gZmllbGRzCiAgICAkZm9ybV9maWVsZHMgPSBhcnJheSggCiAgICAgICAgJ25hbWUnID0+IGFycmF5KCdmaWVsZCcgPT4gJ3RleHRib3gnLCAnbGFiZWwnID0+ICdZb3VyIEZ1bGwgTmFtZScsICdyZXF1aXJlZCcgPT4gMSwgJ3BsYWNlaG9sZGVyJyA9PiAnRW50ZXIgeW91ciBuYW1lLi4uJykKICAgICk7CgoKICAgIC8vIEFkZCBzdWJtaXQgYnV0dG9uCiAgICBpZiAoaXNzZXQoJGRhdGFbJ3JlY29yZF9pZCddKSAmJiAkZGF0YVsncmVjb3JkX2lkJ10gPiAwKSB7IAogICAgICAgICRmb3JtX2ZpZWxkc1snc3VibWl0J10gPSBhcnJheSgnZmllbGQnID0+ICdzdWJtaXQnLCAndmFsdWUnID0+ICd1cGRhdGUnLCAnbGFiZWwnID0+ICdVcGRhdGUgUmVjb3JkJyk7CiAgICB9IGVsc2UgeyAKICAgICAgICAkZm9ybV9maWVsZHNbJ3N1Ym1pdCddID0gYXJyYXkoJ2ZpZWxkJyA9PiAnc3VibWl0JywgJ3ZhbHVlJyA9PiAnY3JlYXRlJywgJ2xhYmVsJyA9PiAnQ3JlYXRlIE5ldyBSZWNvcmQnKTsKICAgIH0KCiAgICAvLyBSZXR1cm4KICAgIHJldHVybiAkZm9ybV9maWVsZHM7Cgp9CgovKioKICogR2V0IHZhbHVlcyBmb3IgYSByZWNvcmQuCiAqCiAqIE1ldGhvZCBpcyBjYWxsZWQgaWYgYSAncmVjb3JkX2lkJyBhdHRyaWJ1dGUgZXhpc3RzIHdpdGhpbiB0aGUgCiAqIGE6ZnVuY3Rpb24gdGFnIHRoYXQgY2FsbHMgdGhlIGZvcm0uICBXaWxsIHJldHJpZXZlIHRoZSB2YWx1ZXMgZnJvbSB0aGUgCiAqIGRhdGFiYXNlIHRvIHBvcHVsYXRlIHRoZSBmb3JtIGZpZWxkcyB3aXRoLgogKgogKiAgIEBwYXJhbSBzdHJpbmcgJHJlY29yZF9pZCBUaGUgdmFsdWUgb2YgdGhlICdyZWNvcmRfaWQnIGF0dHJpYnV0ZSBmcm9tIHRoZSBlOmZ1bmN0aW9uIHRhZy4KICoKICogICBAcmV0dXJuIGFycmF5IEFuIGFycmF5IG9mIGtleS12YWx1ZSBwYWlycyBjb250YWluZyB0aGUgdmFsdWVzIG9mIHRoZSBmb3JtIGZpZWxkcy4KICovCnB1YmxpYyBmdW5jdGlvbiBnZXRfcmVjb3JkKHN0cmluZyAkcmVjb3JkX2lkKTphcnJheSAKewoKICAgIC8vIEdldCByZWNvcmQKICAgICRyb3cgPSBhcnJheSgpOwoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRyb3c7Cgp9CgovKioKICogQWRkaXRpb25hbCBmb3JtIHZhbGlkYXRpb24uCiAqIAogKiBBbGxvd3MgZm9yIGFkZGl0aW9uYWwgdmFsaWRhdGlvbiBvZiB0aGUgc3VibWl0dGVkIGZvcm0uICAKICogVGhlIHN0YW5kYXJkIHNlcnZlci1zaWRlIHZhbGlkYXRpb24gY2hlY2tzIGFyZSBjYXJyaWVkIG91dCwgYXV0b21hdGljYWxseSBhcyAKICogZGVzaWduYXRlZCBpbiB0aGUgJGZvcm1fZmllbGRzIGRlZmluZWQgZm9yIHRoaXMgZm9ybS4gIEhvd2V2ZXIsIHRoaXMgCiAqIGFsbG93cyBhZGRpdGlvbmFsIHZhbGlkYXRpb24gaWYgd2FycmFudGVkLgogKgogKiAgICAgQHBhcmFtIGFycmF5ICRkYXRhIEFueSBhcnJheSBvZiBkYXRhIHBhc3NlZCB0byB0aGUgcmVnaXN0cnk6OnZhbGlkYXRlX2Zvcm0oKSBtZXRob2QuICBVc2VkIHRvIHZhbGlkYXRlIGJhc2VkIG9uIGV4aXN0aW5nIHJlY29yZHMgLyByb3dzIChlZy4gZHVwbG9jYXRlIHVzZXJuYW1lIGNoZWNrLCBidXQgZG9uJ3QgaW5jbHVkZSB0aGUgY3VycmVudCB1c2VyKS4KICovCnB1YmxpYyBmdW5jdGlvbiB2YWxpZGF0ZShhcnJheSAkZGF0YSA9IGFycmF5KCkpIAp7CgogICAgLy8gQWRkaXRpb25hbCB2YWxpZGF0aW9uIGNoZWNrcwoKfQoKfQoK', 
        'htmlfunc' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxodG1sZnVuYzsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcc3ZjXGRiOwp1c2UgYXBleFxzdmNcZGVidWc7CgovKioKICogQ2xhc3MgdG8gaGFuZGxlIHRoZSBIVE1MIGZ1bmN0aW9uLCB3aGljaCByZXBsYWNlcyAKICogdGhlIDxhOmZ1bmN0aW9uPiB0YWdzIHdpdGhpbiB0ZW1wbGF0ZXMgdG8gYW55dGhpbmcgCiAqIHlvdSB3aXNoLgogKi8KY2xhc3MgfmFsaWFzfgp7CgovKioKICogUmVwbGFjZXMgdGhlIGNhbGxpbmcgPGU6ZnVuY3Rpb24+IHRhZyB3aXRoIHRoZSByZXN1bHRpbmcgCiAqIHN0cmluZyBvZiB0aGlzIGZ1bmN0aW9uLgogKiAKICogICBAcGFyYW0gc3RyaW5nICRodG1sIFRoZSBjb250ZW50cyBvZiB0aGUgVFBMIGZpbGUsIGlmIGV4aXN0cywgbG9jYXRlZCBhdCAvdmlld3MvaHRtbGZ1bmMvPHBhY2thZ2U+LzxhbGlhcz4udHBsCiAqICAgQHBhcmFtIGFycmF5ICRkYXRhIFRoZSBhdHRyaWJ1dGVzIHdpdGhpbiB0aGUgY2FsbGluZyBlOmZ1bmN0aW9uPiB0YWcuCiAqCiAqICAgQHJldHVybiBzdHJpbmcgVGhlIHJlc3VsdGluZyBIVE1MIGNvZGUsIHdoaWNoIHRoZSA8ZTpmdW5jdGlvbj4gdGFnIHdpdGhpbiB0aGUgdGVtcGxhdGUgaXMgcmVwbGFjZWQgd2l0aC4KICovCnB1YmxpYyBmdW5jdGlvbiBwcm9jZXNzKHN0cmluZyAkaHRtbCwgYXJyYXkgJGRhdGEgPSBhcnJheSgpKTpzdHJpbmcKewoKCiAgICAvLyBSZXR1cm4KICAgIHJldHVybiAkaHRtbDsKCn0KCn0KCg==', 
        'lib' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlfjsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcc3ZjXGRiOwp1c2UgYXBleFxzdmNcZGVidWc7CgovKioKICogQmxhbmsgbGlicmFyeSBmaWxlIHdoZXJlIHlvdSBjYW4gYWRkIAogKiBhbnkgYW5kIGFsbCBtZXRob2RzIC8gcHJvcGVydGllcyB5b3UgZGVzaXJlLgogKi8KY2xhc3MgfmFsaWFzfgp7CgoKfQoK', 
        'modal' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxtb2RhbDsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcc3ZjXGRiOwp1c2UgYXBleFxzdmNcZGVidWc7CgovKioKICogQ2xhc3MgdGhhdCBoYW5kbGVzIHRoZSBtb2RhbCAtLSB0aGUgY2VudGVyIAogKiBwb3AtdXAgcGFuZWwuCiAqLwpjbGFzcyB+YWxpYXN+CnsKCi8qKgogKiogU2hvdyB0aGUgbW9kYWwgYm94LiAgVXNlZCB0byBnYXRoZXIgYW55IAogKiBuZWNlc3NhcnkgZGF0YWJhc2UgaW5mb3JtYXRpb24sIGFuZCBhc3NpZ24gdGVtcGxhdGUgdmFyaWFibGVzLCBldGMuCiAqLwoKcHVibGljIGZ1bmN0aW9uIHNob3coKQp7CgoKfQoKfQoK', 
        'tabcontrol' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflx0YWJjb250cm9sOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xkZWJ1ZzsKCi8qKgogKiBDbGFzcyB0aGF0IGhhbmRsZXMgdGhlIHRhYiBjb250cm9sLCBhbmQgaXMgZXhlY3V0ZWQgCiAqIGV2ZXJ5IHRpbWUgdGhlIHRhYiBjb250cm9sIGlzIGRpc3BsYXllZC4KICovCmNsYXNzIH5hbGlhc34KewoKICAgIC8vIERlZmluZSB0YWIgcGFnZXMKICAgIHB1YmxpYyAkdGFicGFnZXMgPSBhcnJheSgKICAgICAgICAnZ2VuZXJhbCcgPT4gJ0dlbmVyYWwgU2V0dGluZ3NlJyAKICAgICk7CgovKioKICogUHJvY2VzcyB0aGUgdGFiIGNvbnRyb2wuCiAqCiAqIElzIGV4ZWN1dGVkIGV2ZXJ5IHRpbWUgdGhlIHRhYiBjb250cm9sIGlzIGRpc3BsYXllZCwgCiAqIGlzIHVzZWQgdG8gcGVyZm9ybSBhbnkgYWN0aW9ucyBzdWJtaXR0ZWQgd2l0aGluIGZvcm1zIAogKiBvZiB0aGUgdGFiIGNvbnRyb2wsIGFuZCBtYWlubHkgdG8gcmV0cmlldmUgYW5kIGFzc2lnbiB2YXJpYWJsZXMgCiAqIHRvIHRoZSB0ZW1wbGF0ZSBlbmdpbmUuCiAqCiAqICAgICBAcGFyYW0gYXJyYXkgJGRhdGEgVGhlIGF0dHJpYnV0ZXMgY29udGFpbmVkIHdpdGhpbiB0aGUgPGU6ZnVuY3Rpb24+IHRhZyB0aGF0IGNhbGxlZCB0aGUgdGFiIGNvbnRyb2wuCiAqLwpwdWJsaWMgZnVuY3Rpb24gcHJvY2VzcyhhcnJheSAkZGF0YSkgCnsKCgp9Cgp9Cgo=', 
        'tabpage' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflx0YWJjb250cm9sXH5wYXJlbnR+OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xkZWJ1ZzsKCi8qKgogKiBIYW5kbGVzIHRoZSBzcGVjaWZpY3Mgb2YgdGhlIG9uZSB0YWIgcGFnZSwgYW5kIGlzIAogKiBleGVjdXRlZCBldmVyeSB0aW1lIHRoZSB0YWIgcGFnZSBpcyBkaXNwbGF5ZWQuCiAqLwpjbGFzcyB+YWxpYXN+IAp7CgogICAgLy8gUGFnZSB2YXJpYWJsZXMKICAgIHB1YmxpYyAkcG9zaXRpb24gPSAnYm90dG9tJzsKICAgIHB1YmxpYyAkbmFtZSA9ICd+YWxpYXNfdWN+JzsKCi8qKgogKiBQcm9jZXNzIHRoZSB0YWIgcGFnZS4KICoKICogRXhlY3V0ZXMgZXZlcnkgdGltZSB0aGUgdGFiIGNvbnRyb2wgaXMgZGlzcGxheWVkLCBhbmQgdXNlZCAKICogdG8gZXhlY3V0ZSBhbnkgbmVjZXNzYXJ5IGFjdGlvbnMgZnJvbSBmb3JtcyBmaWxsZWQgb3V0IAogKiBvbiB0aGUgdGFiIHBhZ2UsIGFuZCBtaWFubHkgdG8gdHJlaWV2ZSB2YXJpYWJsZXMgYW5kIGFzc2lnbiAKICogdGhlbSB0byB0aGUgdGVtcGxhdGUuCiAqCiAqICAgICBAcGFyYW0gYXJyYXkgJGRhdGEgVGhlIGF0dHJpYnV0ZXMgY29udGFpbmQgd2l0aGluIHRoZSA8ZTpmdW5jdGlvbj4gdGFnIHRoYXQgY2FsbGVkIHRoZSB0YWIgY29udHJvbAogKi8KcHVibGljIGZ1bmN0aW9uIHByb2Nlc3MoYXJyYXkgJGRhdGEgPSBhcnJheSgpKSAKewoKCn0KCn0KCg==', 
        'table' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflx0YWJsZTsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcc3ZjXGRiOwp1c2UgYXBleFxzdmNcZGVidWc7CgoKLyoqCiAqIEhhbmRsZXMgdGhlIHRhYmxlIGluY2x1ZGluZyBvYnRhaW5pbmcgdGhlIHJvd3MgdG8gCiAqIGRpc3BsYXksIHRvdGFsIHJvd3MgaW4gdGhlIHRhYmxlLCBmb3JtYXR0aW5nIG9mIGNlbGxzLCBldGMuCiAqLwpjbGFzcyB+YWxpYXN+CnsKCiAgICAvLyBDb2x1bW5zCiAgICBwdWJsaWMgJGNvbHVtbnMgPSBhcnJheSgKICAgICAgICAnaWQnID0+ICdJRCcKICAgICk7CgogICAgLy8gU29ydGFibGUgY29sdW1ucwogICAgcHVibGljICRzb3J0YWJsZSA9IGFycmF5KCdpZCcpOwoKICAgIC8vIE90aGVyIHZhcmlhYmxlcwogICAgcHVibGljICRyb3dzX3Blcl9wYWdlID0gMjU7CiAgICBwdWJsaWMgJGhhc19zZWFyY2ggPSBmYWxzZTsKCiAgICAvLyBGb3JtIGZpZWxkIChsZWZ0LW1vc3QgY29sdW1uKQogICAgcHVibGljICRmb3JtX2ZpZWxkID0gJ2NoZWNrYm94JzsKICAgIHB1YmxpYyAkZm9ybV9uYW1lID0gJ35hbGlhc35faWQnOwogICAgcHVibGljICRmb3JtX3ZhbHVlID0gJ2lkJzsgCgogICAgLy8gRGVsZXRlIGJ1dHRvbgogICAgcHVibGljICRkZWxldGVfYnV0dG9uID0gJ0RlbGV0ZSBDaGVja2VkIH5hbGlhc191Y35zJzsKICAgIHB1YmxpYyAkZGVsZXRlX2RidGFibGUgPSAnJzsKICAgIHB1YmxpYyAkZGVsZXRlX2RiY29sdW1uID0gJyc7CgovKioKICogUGFyc2UgYXR0cmlidXRlcyB3aXRoaW4gPGE6ZnVuY3Rpb24+IHRhZy4KICoKICogUGFzc2VzIHRoZSBhdHRyaWJ1dGVzIGNvbnRhaW5lZCB3aXRoaW4gdGhlIDxlOmZ1bmN0aW9uPiB0YWcgdGhhdCBjYWxsZWQgdGhlIHRhYmxlLgogKiBVc2VkIG1haW5seSB0byBzaG93L2hpZGUgY29sdW1ucywgYW5kIHJldHJpZXZlIHN1YnNldHMgb2YgCiAqIGRhdGEgKGVnLiBzcGVjaWZpYyByZWNvcmRzIGZvciBhIHVzZXIgSUQjKS4KICogCiAoICAgICBAcGFyYW0gYXJyYXkgJGRhdGEgVGhlIGF0dHJpYnV0ZXMgY29udGFpbmVkIHdpdGhpbiB0aGUgPGU6ZnVuY3Rpb24+IHRhZyB0aGF0IGNhbGxlZCB0aGUgdGFibGUuCiAqLwpwdWJsaWMgZnVuY3Rpb24gZ2V0X2F0dHJpYnV0ZXMoYXJyYXkgJGRhdGEgPSBhcnJheSgpKQp7Cgp9CgovKioKICogR2V0IHRvdGFsIHJvd3MuCiAqCiAqIEdldCB0aGUgdG90YWwgbnVtYmVyIG9mIHJvd3MgYXZhaWxhYmxlIGZvciB0aGlzIHRhYmxlLgogKiBUaGlzIGlzIHVzZWQgdG8gZGV0ZXJtaW5lIHBhZ2luYXRpb24gbGlua3MuCiAqIAogKiAgICAgQHBhcmFtIHN0cmluZyAkc2VhcmNoX3Rlcm0gT25seSBhcHBsaWNhYmxlIGlmIHRoZSBBSkFYIHNlYXJjaCBib3ggaGFzIGJlZW4gc3VibWl0dGVkLCBhbmQgaXMgdGhlIHRlcm0gYmVpbmcgc2VhcmNoZWQgZm9yLgogKiAgICAgQHJldHVybiBpbnQgVGhlIHRvdGFsIG51bWJlciBvZiByb3dzIGF2YWlsYWJsZSBmb3IgdGhpcyB0YWJsZS4KICovCnB1YmxpYyBmdW5jdGlvbiBnZXRfdG90YWwoc3RyaW5nICRzZWFyY2hfdGVybSA9ICcnKTppbnQgCnsKCiAgICAvLyBHZXQgdG90YWwKICAgIGlmICgkc2VhcmNoX3Rlcm0gIT0gJycpIHsgCiAgICAgICAgJHRvdGFsID0gREI6OmdldF9maWVsZCgiU0VMRUNUIGNvdW50KCopIEZST00gfnBhY2thZ2V+X35hbGlhc34gV0hFUkUgc29tZV9jb2x1bW4gTElLRSAlbHMiLCAkc2VhcmNoX3Rlcm0pOwogICAgfSBlbHNlIHsgCiAgICAgICAgJHRvdGFsID0gREI6OmdldF9maWVsZCgiU0VMRUNUIGNvdW50KCopIEZST00gfnBhY2thZ2V+X35hbGlhc34iKTsKICAgIH0KICAgIGlmICgkdG90YWwgPT0gJycpIHsgJHRvdGFsID0gMDsgfQoKICAgIC8vIFJldHVybgogICAgcmV0dXJuIChpbnQpICR0b3RhbDsKCn0KCi8qKgogKiBHZXQgcm93cyB0byBkaXNwbGF5CiAqCiAqIEdldHMgdGhlIGFjdHVhbCByb3dzIHRvIGRpc3BsYXkgdG8gdGhlIHdlYiBicm93c2VyLgogKiBVc2VkIGZvciB3aGVuIGluaXRpYWxseSBkaXNwbGF5aW5nIHRoZSB0YWJsZSwgcGx1cyBBSkFYIGJhc2VkIHNlYXJjaCwgCiAqIHNvcnQsIGFuZCBwYWdpbmF0aW9uLgogKgogKiAgICAgQHBhcmFtIGludCAkc3RhcnQgVGhlIG51bWJlciB0byBzdGFydCByZXRyaWV2aW5nIHJvd3MgYXQsIHVzZWQgd2l0aGluIHRoZSBMSU1JVCBjbGF1c2Ugb2YgdGhlIFNRTCBzdGF0ZW1lbnQuCiAqICAgICBAcGFyYW0gc3RyaW5nICRzZWFyY2hfdGVybSBPbmx5IGFwcGxpY2FibGUgaWYgdGhlIEFKQVggYmFzZWQgc2VhcmNoIGJhc2UgaXMgc3VibWl0dGVkLCBhbmQgaXMgdGhlIHRlcm0gYmVpbmcgc2VhcmNoZWQgZm9ybS4KICogICAgIEBwYXJhbSBzdHJpbmcgJG9yZGVyX2J5IE11c3QgaGF2ZSBhIGRlZmF1bHQgdmFsdWUsIGJ1dCBjaGFuZ2VzIHdoZW4gdGhlIHNvcnQgYXJyb3dzIGluIGNvbHVtbiBoZWFkZXJzIGFyZSBjbGlja2VkLiAgVXNlZCB3aXRoaW4gdGhlIE9SREVSIEJZIGNsYXVzZSBpbiB0aGUgU1FMIHN0YXRlbWVudC4KICoKICogICAgIEByZXR1cm4gYXJyYXkgQW4gYXJyYXkgb2YgYXNzb2NpYXRpdmUgYXJyYXlzIGdpdmluZyBrZXktdmFsdWUgcGFpcnMgb2YgdGhlIHJvd3MgdG8gZGlzcGxheS4KICovCnB1YmxpYyBmdW5jdGlvbiBnZXRfcm93cyhpbnQgJHN0YXJ0ID0gMCwgc3RyaW5nICRzZWFyY2hfdGVybSA9ICcnLCBzdHJpbmcgJG9yZGVyX2J5ID0gJ2lkIGFzYycpOmFycmF5IAp7CgogICAgLy8gR2V0IHJvd3MKICAgIGlmICgkc2VhcmNoX3Rlcm0gIT0gJycpIHsgCiAgICAgICAgJHJvd3MgPSBEQjo6cXVlcnkoIlNFTEVDVCAqIEZST00gfnBhY2thZ2V+X35hbGlhc34gV0hFUkUgc29tZV9jb2x1bW4gTElLRSAlbHMgT1JERVIgQlkgJG9yZGVyX2J5IExJTUlUICRzdGFydCwkdGhpcy0+cm93c19wZXJfcGFnZSIsICRzZWFyY2hfdGVybSk7CiAgICB9IGVsc2UgeyAKICAgICAgICAkcm93cyA9IERCOjpxdWVyeSgiU0VMRUNUICogRlJPTSB+cGFja2FnZX5ffmFsaWFzfiBPUkRFUiBCWSAkb3JkZXJfYnkgTElNSVQgJHN0YXJ0LCR0aGlzLT5yb3dzX3Blcl9wYWdlIik7CiAgICB9CgogICAgLy8gR28gdGhyb3VnaCByb3dzCiAgICAkcmVzdWx0cyA9IGFycmF5KCk7CiAgICBmb3JlYWNoICgkcm93cyBhcyAkcm93KSB7IAogICAgICAgIGFycmF5X3B1c2goJHJlc3VsdHMsICR0aGlzLT5mb3JtYXRfcm93KCRyb3cpKTsKICAgIH0KCiAgICAvLyBSZXR1cm4KICAgIHJldHVybiAkcmVzdWx0czsKCn0KCi8qKgogKiBGb3JtYXQgYSBzaW5nbGUgcm93LgogKgogKiBSZXRyaWV2ZXMgcmF3IGRhdGEgZnJvbSB0aGUgZGF0YWJhc2UsIHdoaWNoIG11c3QgYmUgCiAqIGZvcm1hdHRlZCBpbnRvIHVzZXIgcmVhZGFibGUgZm9ybWF0IChlZy4gZm9ybWF0IGFtb3VudHMsIGRhdGVzLCBldGMuKS4KICoKICogICAgIEBwYXJhbSBhcnJheSAkcm93IFRoZSByb3cgZnJvbSB0aGUgZGF0YWJhc2UuCiAqCiAqICAgICBAcmV0dXJuIGFycmF5IFRoZSByZXN1bHRpbmcgYXJyYXkgdGhhdCBzaG91bGQgYmUgZGlzcGxheWVkIHRvIHRoZSBicm93c2VyLgogKi8KcHVibGljIGZ1bmN0aW9uIGZvcm1hdF9yb3coYXJyYXkgJHJvdyk6YXJyYXkgCnsKCiAgICAvLyBGb3JtYXQgcm93CgoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRyb3c7Cgp9Cgp9Cgo=', 
        'test' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSB0ZXN0c1x+cGFja2FnZX47Cgp1c2UgYXBleFxhcHA7CnVzZSBhcGV4XHN2Y1xkYjsKdXNlIGFwZXhcc3ZjXGRlYnVnOwp1c2UgYXBleFxhcHBcdGVzdHNcdGVzdDsKCgovKioKICogQWRkIGFueSBuZWNlc3NhcnkgcGhwVW5pdCB0ZXN0IG1ldGhvZHMgaW50byB0aGlzIGNsYXNzLiAgWW91IG1heSBleGVjdXRlIGFsbCAKICogdGVzdHMgYnkgcnVubmluZzogIHBocCBhcGV4LnBocCB0ZXN0IH5wYWNrYWdlfgogKi8KY2xhc3MgdGVzdF9+YWxpYXN+IGV4dGVuZHMgdGVzdAp7CgovKioKICogc2V0VXAKICovCnB1YmxpYyBmdW5jdGlvbiBzZXRVcCgpOnZvaWQKewoKICAgIC8vIEdldCBhcHAKICAgIGlmICghJGFwcCA9IGFwcDo6Z2V0X2luc3RhbmNlKCkpIHsgCiAgICAgICAgJGFwcCA9IG5ldyBhcHAoJ3Rlc3QnKTsKICAgIH0KCn0KCi8qKgogKiB0ZWFyRG93bgogKi8KcHVibGljIGZ1bmN0aW9uIHRlYXJEb3duKCk6dm9pZAp7Cgp9CgoKCn0KCg==', 
        'worker' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflx3b3JrZXI7Cgp1c2UgYXBleFxhcHA7CnVzZSBhcGV4XHN2Y1xkYjsKdXNlIGFwZXhcc3ZjXGRlYnVnOwp1c2UgYXBleFxhcHBcaW50ZXJmYWNlc1xtc2dcRXZlbnRNZXNzYWdlSW50ZXJmYWNlOwoKLyoqCiAqIENsYXNzIHRoYXQgaGFuZGxlcyBhIHdvcmtlciAvIGxpc3RlbmVyIGNvbXBvbmVudCwgd2hpY2ggaXMgCiAqIHVzZWQgZm9yIG9uZS13YXkgZGlyZWN0IGFuZCB0d28td2F5IFJQQyBtZXNzYWdlcyB2aWEgUmFiYml0TVEsIAogKiBhbmQgc2hvdWxkIGJlIHV0aWxpemVkIGEgZ29vZCBkZWFsLgogKi8KY2xhc3MgfmFsaWFzfgp7CgoKCn0KCg=='
    );

/**
 * Create a new component.  Used via the apex.php script, to create a new 
 * component including necessary files. 
 *
 * @param string $type The type of component (template, worker, lib, etc.)
 * @param string $comp_alias Alias of the component in Apex format (ie. PACKAGE:[PARENT:]ALIAS
 * @param string $owner Optional owner, only required for a few components (controller, tab_page, worker)
 */
public static function create(string $type, string $comp_alias, string $owner = '')
{ 

    // Perform necessary checks
    list($alias, $parent, $package, $value, $owner) = self::add_checks($type, $comp_alias, '', $owner);

    // Create view, if needed
    if ($type == 'view') { 
        return self::create_view($alias, $owner);
    }

    // Debug
    debug::add(4, tr("Starting to create component, type: {1}, package: {2}, parent: {3}, alias: {4}, owner: {5}", $type, $package, $parent, $alias, $owner));

    // Get PHP filename
    $php_file = components::get_file($type, $alias, $package, $parent);
    if ($php_file == '') { 
        throw new ComponentException('no_php_file', $type, '', $alias, $package, $parent);
    }
    $php_file = SITE_PATH . '/' . $php_file;

    // Check if PHP file exists already
    if (file_exists($php_file)) { 
        throw new ComponentException('php_file_exists', $type, '', $alias, $package, $parent);
    }

    // Get default PHP code
    if ($type == 'controller' && $parent != '') {
        $class_file = SITE_PATH . '/src/' . $package . '/controller/' . $parent . '.php';
        if (file_exists($class_file)) {
            require_once($class_file);
            $class_name = "apex\\" . $package . "\\controller\\" . $parent;
            $class = new $class_name();
            $code = $class->default_code ?? '';
        } else { 
            $code = self::$code_templates['controller'];
        }
        $code = base64_decode($code);

    } else { 
        $code_type = ($type == 'controller' && $parent == '') ? 'controller_parent' : $type;
        $code = base64_decode(self::$code_templates[$code_type]);
    }

    // Replace merge fields in default code as needed
    $code = str_replace("~package~", $package, $code);
    $code = str_replace("~parent~", $parent, $code);
    $code = str_replace("~alias~", $alias, $code);
    $code = str_replace("~alias_uc~", ucwords($alias), $code);

    // Save file
    io::create_dir(dirname($php_file));
    file_put_contents($php_file, $code);

    // Debug
    debug::add(5, tr("Created new PHP file for components at {1}", $php_file));

    // Save .tpl file as needed
    $tpl_file = components::get_tpl_file($type, $alias, $package, $parent);
    if ($tpl_file != '') { 
        io::create_dir(dirname(SITE_PATH . '/' . $tpl_file));
        file_put_contents(SITE_PATH . '/' . $tpl_file, '');
    }

// Create tab control directory
    if ($type == 'tabcontrol') { 
        io::create_dir(SITE_PATH . '/src/' . $package . '/tabcontrol/' . $alias);
        io::create_dir(SITE_PATH . '/views/components/tabpage/' . $package . '/' . $alias);
    }

    // Add component
    self::add($type, $comp_alias, $value, 0, $owner);

    // Add crontab job, if needed
    if ($type == 'cron') { 
        self::add_crontab($package, $alias);
    }

    // Debug
debug::add(4, tr("Successfully created new component, type: {1}, package: {2}, parent: {3}, alias: {4}", $type, $package, $parent, $alias));

    // Return
    return array($type, $alias, $package, $parent);

}

/**
 * Create a new template, including necessary files.  Used by the apex.php 
 * script.
 *
 * @param string $uri The URI of the view
 * @param string $package The alias of the package owner
 */
protected static function create_view(string $uri, string $package)
{ 

    // Check uri
    if ($uri == '' || preg_match("/\s/", $uri)) { 
        throw new ComponentException('invalid_template_uri', 'template', '', $uri);
    }

    // Debug
    debug::add(4, tr("Starting to create new template at, {1}", $uri));

    // Set filenames
    $uri = trim(strtolower($uri), '/');
    $tpl_file = SITE_PATH . '/views/tpl/' . $uri . '.tpl';
    $php_file = SITE_PATH . '/views/php/' . $uri . '.php';

    // Create directories as needed
    io::create_dir(dirname($tpl_file));
    io::create_dir(dirname($php_file));

    // Save files
    file_put_contents($tpl_file, '');
    file_put_contents($php_file, base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XHZpZXdzOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1x2aWV3Owp1c2UgYXBleFxzdmNcZGVidWc7CgovKioKICogQWxsIGNvZGUgYmVsb3cgdGhpcyBsaW5lIGlzIGF1dG9tYXRpY2FsbHkgZXhlY3V0ZWQgd2hlbiB0aGlzIHRlbXBsYXRlIGlzIHZpZXdlZCwgCiAqIGFuZCB1c2VkIHRvIHBlcmZvcm0gYW55IG5lY2Vzc2FyeSB0ZW1wbGF0ZSBzcGVjaWZpYyBhY3Rpb25zLgogKi8KCgoK'));

    // Add component
    self::add('view', $uri, '', 0, $package);

    // Debug
    debug::add(4, tr("Successfully created new template at, {1}", $uri));

    // Return
    return array('view', $uri, $package, '');

}

/**
 * Add component to database 
 *
 * Add a new component into the database.  This will not actually create the 
 * necessary PHP / TPL files, and instead will only add the necessary row(s) 
 * into the database. 
 *
 * @param string $type The type of component (htmlfunc, worker, hash, etc.)
 * @param string $comp_alias Alias of component in standard Apex format (PACKAGE:[PARENT:]ALIAS)
 * @param string $value Only required for a few components such as 'config', and is the value of the component
 * @param int $order_num The order num of the component.
 * @param string $owner Only needed for controller and tabpage components, and is the owner package of the component.
 */
public static function add(string $type, string $comp_alias, string $value = '', int $order_num = 0, string $owner = '')
{ 

    // Perform necessary checks
    list($alias, $parent, $package, $value, $owner) = self::add_checks($type, $comp_alias, $value, $owner);

    // Update component, if needed
    if ($row = db::get_row("SELECT * FROM internal_components WHERE type = %s AND package = %s AND parent = %s AND alias = %s", $type, $package, $parent, $alias)) { 

        // Set updates
        $updates = array();
        if ($order_num > 0) { $updates['order_num'] = $order_num; }
        if ($value != '' && $type != 'config') { 
            $updates['value'] = $value;
        }

        // Debug
        debug::add(5, tr("Updating existing component, type: {1}, package: {2}, parent: {3}, alias: {4}", $type, $package, $parent, $alias));

        // Update database
        if (count($updates) > 0) { 
            db::update('internal_components', $updates, "id = %i", $row['id']);
        }

        // Reorder tab control, if needed
        if ($type == 'tabpage') { 
            $pkg_client = new package_client($package);
            $pkg_client->reorder_tab_control($parent, $package);
        }

        // Return
        return true;
    }

    // Debug
    debug::add(4, tr("Adding new component to database, type: {1}, package: {2}, parent: {3}, alias: {4}", $type, $package, $parent, $alias));

    // Add component to DB
    db::insert('internal_components', array(
        'order_num' => $order_num,
        'type' => $type,
        'owner' => $owner,
        'package' => $package,
        'parent' => $parent,
        'alias' => $alias,
        'value' => $value)
    );
    $component_id = db::insert_id();

    // Add crontab job, if needed
    if ($type == 'cron') { 
        self::add_crontab($package, $alias);
    }

    // Add to redis
    redis::sadd('config:components', implode(":", array($type, $package, $parent, $alias)));

    // Add to redis -- components_packages
    $chk = $type . ':' . $alias;
    if (!$value = redis::hget('config:components_package', $chk)) { 
        redis::hset('config:components_package', $chk, $package);
    } elseif ($value != $package) { 
        redis::hset('config:components_package', $chk, 2);
    }

}

/**
 * Perform all necessary checks on a component before adding it to the 
 * database. 
 *
 * @param string $type The type of component (htmlfunc, worker, hash, etc.)
 * @param string $comp_alias Alias of component in standard Apex format (PACKAGE:[PARENT:]ALIAS)
 * @param string $value Only required for a few components such as 'config', and is the value of the component
 * @param string $owner Only needed for controller and tabpage components, and is the owner package of the component.
 */
protected static function add_checks(string $type, string $comp_alias, string $value = '', string $owner = '')
{ 

    // Split alias
    $vars = explode(":", $comp_alias);
    if ($type != 'view' && count($vars) < 2 || count($vars) > 3) { 
        throw new ComponentException('invalid_comp_alias', $type, $comp_alias);
    }

    // Set component vars
    $package = $type == 'view' ? $owner : array_shift($vars);
    $parent = isset($vars[1]) ? $vars[0] : '';
    $alias = $vars[1] ?? $vars[0];

    // Check parent
    if ($parent == '' && ($type == 'tabpage' || $type == 'hash_var')) { 
        throw new ComponentException('no_parent', $type, $comp_alias);
    }

    // Ensure parent exists
    if ($parent != '') { 

        // Get parent type
        if ($type == 'tabpage') { $parent_type = 'tabcontrol'; }
        elseif ($type == 'hash_var') { $parent_type = 'hash'; }
        else { $parent_type = $type; }

        // Check parent
        if (!components::check($parent_type, $package . ':' . $parent)) { 
            throw new ComponentException('parent_not_exists', $type, $comp_alias);
        }
    }

    // Check owner
    if ($owner == '' && (($type == 'controller' && $parent != '') || ($type == 'tabpage'))) { 
        throw new ComponentException('no_owner', $type, $comp_alias);
    } elseif ($owner == '' && $value == '' && $type == 'worker') { 
        throw new ComponentException('no_worker_routing_key');
    }

    // Set value for worker
    if ($type == 'worker' && $value == '') { 
        $value = $owner;
        $owner = '';
    }

    // Set owner
    if ($owner == '') { $owner = $package; }

    // Return
    return array($alias, $parent, $package, $value, $owner);

}

/**
 * Adds a crontab job to the 'internal_crontab' table of the database 
 *
 * @param string $package The package the cron job is being added to.
 * @param string $alias The alias of the crontab job.
 */
protected static function add_crontab(string $package, string $alias)
{ 

    // Check if crontab job already exists
    if ($row = db::get_row("SELECT * FROM internal_crontab WHERE package = %s AND alias = %s", $package, $alias)) { 
        return true;
    }

    // Load file
    if (!$cron = components::load('cron', $alias, $package)) { 
        throw new ComponentException('no_load', 'cron', '', $alias, $package);
    }

    // Get date
    $next_date = date::add_interval($cron->time_interval, time(), false);
    $name = isset($cron->name) ? $cron->name : $alias;

    // Add to database
    db::insert('internal_crontab', array(
        'time_interval' => $cron->time_interval,
        'nextrun_time' => $next_date,
        'package' => $package,
        'alias' => $alias,
        'display_name' => $name)
    );

}

/**
 * Deletes a component from the database, including corresponding file(s). 
 *
 * @param string $type The type of component being deleted (eg. 'config', 'htmlfunc', etc.).
 * @param string $comp_alias Alias of component to delete in standard Apex format (ie. PACKAGE:[PARENT:]ALIAS)
 *
 * @return bool Whether or not the operation was successful.
 */
public static function remove(string $type, string $comp_alias)
{ 

    // Debug
    debug::add(4, tr("Deleting component, type: {1}, comp alias: {2}", $type, $comp_alias));

    // Check if component exists
    if (!list($package, $parent, $alias) = components::check($type, $comp_alias)) { 
        return true;
    }

    // Get component row
    if (!$row = db::get_row("SELECT * FROM internal_components WHERE package = %s AND type = %s AND parent = %s AND alias = %s", $package, $type, $parent, $alias)) { 
        return true;
    }

    // Delete files
    $files = components::get_all_files($type, $alias, $package, $parent);
    foreach ($files as $file) { 
        if (!file_exists(SITE_PATH . '/' . $file)) { continue; }
        @unlink(SITE_PATH . '/' . $file);

        // Remove parent directory, if empty
        $child_files = io::parse_dir(dirname(SITE_PATH . '/' . $file));
        if (count($child_files) == 0) { 
            io::remove_dir(dirname(SITE_PATH . '/' . $file));
        }
    }

    // Delete tab control directory, if needed
    if ($type == 'tabcontrol') { 
        io::remove_dir(SITE_PATH . '/src/' . $package . '/tabcontrol/' . $alias);
        io::remove_dir(SITE_PATH . '/views/tabpage/' . $package . '/' . $alias);
    }

    // Delete children, if needed
    if ($type == 'tabcontrol' || ($type == 'controller' && $parent == '')) { 
        $child_type = $type == 'tabcontrol' ? 'tabpage' : 'controller';

        // Go through child components
        $children = db::query("SELECT * FROM internal_components WHERE type = %s AND package = %s AND parent = %s", $child_type, $package, $alias);
        foreach ($children as $crow) { 
        $del_alias = $crow['package'] . ':' . $crow['parent'] . ':' . $crow['alias'];
            self::remove($crow['type'], $del_alias);
        }
    }

    // Delete from database
    db::query("DELETE FROM internal_components WHERE package = %s AND type = %s AND parent = %s AND alias = %s", $package, $type, $parent, $alias);
    if ($type == 'cron') { 
        db::query("DELETE FROM internal_crontab WHERE package = %s AND alias = %s", $package, $alias);
    }

    // Delete from redis
    redis::srem('config:components', implode(":", array($type, $package, $parent, $alias)));

    // Update redis components package as needed
    $chk = $type . ':' . $alias;
    if (redis::hget('config:components_package', $chk) != 2) { 
        redis::hdel('config:components_package', $chk);
    } else { 
        $chk_packages = db::get_column("SELECT package FROM internal_components WHERE type = %s AND alias = %s AND parent = %s", $package, $alias, $parent);
        if (count($chk_packages) == 1) { 
            redis::hset('config:components_package', $chk, $chk_packages[0]);
        }
    }

    // Delete config / hash from redis
    if ($type == 'config') { 
        redis::hdel('config', $package . ':' . $alias);
    } elseif ($type == 'hash') { 
        redis::hdel('hash', $package . ':' . $alias);
    }

    // Debug / log
    debug::add(2, tr("Deleted component.  owner {1}, type: {2}, package: {3}, alias {4}, parent: {5}", $package, $type, $package, $alias, $parent));

    // Return
    return true;

}


}

