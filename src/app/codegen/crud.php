<?php
declare(strict_types = 1);

namespace apex\app\codegen;

use apex\app;
use apex\libc\{db, redis, debug, components};
use apex\app\pkg\pkg_component;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\String\Inflector\EnglishInflector;
use apex\app\exceptions\{ApexException, PackageException, ComponentException};


/**
 * This class handles all CRUD creation functionality via the 
 * "apex create crud" CLI command.  Please view the documentation 
 * for further details.
 */
class crud
{

    // Properties
    private string $package;
    private string $dbtable;
    private string $alias;
    private string $alias_plural;
    private array $columns = [];
    private array $created_files = [];

    // Code templates
    private array $code_templates = [
        'form' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxmb3JtOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZGVidWd9Owp1c2UgYXBleFxhcHBcZXhjZXB0aW9uc1xBcGV4RXhjZXB0aW9uOwoKCi8qKiAKICogQ2xhc3MgZm9yIHRoZSBIVE1MIGZvcm0gdG8gZWFzaWx5IGRlc2lnbiBmdWxseSBmdW5jdGlvbmFsIAogKiBmb3JtcyB3aXRoIGJvdGgsIEphdmFzY3JpcHQgYW5kIHNlcnZlci1zaWRlIHZhbGlkYXRpb24uCiAqLwpjbGFzcyB+YWxpYXN+CnsKCiAgICAvLyBQcm9wZXJ0aWVzCiAgICBwdWJsaWMgJGFsbG93X3Bvc3RfdmFsdWVzID0gMTsKCi8qKgogKiBEZWZpbmVzIHRoZSBmb3JtIGZpZWxkcyBpbmNsdWRlZCB3aXRoaW4gdGhlIEhUTUwgZm9ybS4KICogCiAqICAgQHBhcmFtIGFycmF5ICRkYXRhIEFuIGFycmF5IG9mIGFsbCBhdHRyaWJ1dGVzIHNwZWNpZmllZCB3aXRoaW4gdGhlIGU6ZnVuY3Rpb24gdGFnIHRoYXQgY2FsbGVkIHRoZSBmb3JtLiAKICoKICogICBAcmV0dXJuIGFycmF5IEtleXMgb2YgdGhlIGFycmF5IGFyZSB0aGUgbmFtZXMgb2YgdGhlIGZvcm0gZmllbGRzLiAgVmFsdWVzIG9mIHRoZSBhcnJheSBhcmUgYXJyYXlzIHRoYXQgc3BlY2lmeSB0aGUgYXR0cmlidXRlcyBvZiB0aGUgZm9ybSBmaWVsZC4gIFJlZmVyIHRvIGRvY3VtZW50YXRpb24gZm9yIGRldGFpbHMuCiAqLwpwdWJsaWMgZnVuY3Rpb24gZ2V0X2ZpZWxkcyhhcnJheSAkZGF0YSA9IGFycmF5KCkpOmFycmF5CnsKCiAgICAvLyBTZXQgZm9ybSBmaWVsZHMKICAgICRmb3JtX2ZpZWxkcyA9IGFycmF5KCAKfmZvcm1fZmllbGRzfgogICAgKTsKCiAgICAvLyBBZGQgc3VibWl0IGJ1dHRvbgogICAgaWYgKGlzc2V0KCRkYXRhWydyZWNvcmRfaWQnXSkgJiYgJGRhdGFbJ3JlY29yZF9pZCddID4gMCkgeyAKICAgICAgICAkZm9ybV9maWVsZHNbJ3N1Ym1pdCddID0gYXJyYXkoJ2ZpZWxkJyA9PiAnc3VibWl0JywgJ3ZhbHVlJyA9PiAndXBkYXRlJywgJ2xhYmVsJyA9PiAnVXBkYXRlIH5hbGlhc19zaW5nbGVfdGN+Jyk7CiAgICB9IGVsc2UgeyAKICAgICAgICAkZm9ybV9maWVsZHNbJ3N1Ym1pdCddID0gYXJyYXkoJ2ZpZWxkJyA9PiAnc3VibWl0JywgJ3ZhbHVlJyA9PiAnY3JlYXRlJywgJ2xhYmVsJyA9PiAnQ3JlYXRlIE5ldyB+YWxpYXNfc2luZ2xlX3RjficpOwogICAgfQoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRmb3JtX2ZpZWxkczsKCn0KCi8qKgogKiBHZXQgdmFsdWVzIGZvciBhIHJlY29yZC4KICoKICogTWV0aG9kIGlzIGNhbGxlZCBpZiBhICdyZWNvcmRfaWQnIGF0dHJpYnV0ZSBleGlzdHMgd2l0aGluIHRoZSAKICogYTpmdW5jdGlvbiB0YWcgdGhhdCBjYWxscyB0aGUgZm9ybS4gIFdpbGwgcmV0cmlldmUgdGhlIHZhbHVlcyBmcm9tIHRoZSAKICogZGF0YWJhc2UgdG8gcG9wdWxhdGUgdGhlIGZvcm0gZmllbGRzIHdpdGguCiAqCiAqICAgQHBhcmFtIHN0cmluZyAkcmVjb3JkX2lkIFRoZSB2YWx1ZSBvZiB0aGUgJ3JlY29yZF9pZCcgYXR0cmlidXRlIGZyb20gdGhlIGU6ZnVuY3Rpb24gdGFnLgogKgogKiAgIEByZXR1cm4gYXJyYXkgQW4gYXJyYXkgb2Yga2V5LXZhbHVlIHBhaXJzIGNvbnRhaW5nIHRoZSB2YWx1ZXMgb2YgdGhlIGZvcm0gZmllbGRzLgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9yZWNvcmQoc3RyaW5nICRyZWNvcmRfaWQpOmFycmF5IAp7CgogICAgLy8gR2V0IHJlY29yZAogICAgaWYgKCEkcm93ID0gZGI6OmdldF9pZHJvdygnfmRidGFibGV+JywgJHJlY29yZF9pZCkpIHsgCiAgICAgICAgdGhyb3cgbmV3IEFwZXhFeGNlcHRpb24oJ2Vycm9yJywgdHIoIlJlY29yZCBkb2VzIG5vdCBleGlzdCB3aXRoaW4gJ35kYnRhYmxlficgd2l0aCBpZCMgezF9IiwgJHJlY29yZF9pZCkpOwogICAgfQoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRyb3c7Cgp9CgovKioKICogQWRkaXRpb25hbCBmb3JtIHZhbGlkYXRpb24uCiAqIAogKiBBbGxvd3MgZm9yIGFkZGl0aW9uYWwgdmFsaWRhdGlvbiBvZiB0aGUgc3VibWl0dGVkIGZvcm0uICAKICogVGhlIHN0YW5kYXJkIHNlcnZlci1zaWRlIHZhbGlkYXRpb24gY2hlY2tzIGFyZSBjYXJyaWVkIG91dCwgYXV0b21hdGljYWxseSBhcyAKICogZGVzaWduYXRlZCBpbiB0aGUgJGZvcm1fZmllbGRzIGRlZmluZWQgZm9yIHRoaXMgZm9ybS4gIEhvd2V2ZXIsIHRoaXMgCiAqIGFsbG93cyBhZGRpdGlvbmFsIHZhbGlkYXRpb24gaWYgd2FycmFudGVkLgogKgogKiAgICAgQHBhcmFtIGFycmF5ICRkYXRhIEFueSBhcnJheSBvZiBkYXRhIHBhc3NlZCB0byB0aGUgcmVnaXN0cnk6OnZhbGlkYXRlX2Zvcm0oKSBtZXRob2QuICBVc2VkIHRvIHZhbGlkYXRlIGJhc2VkIG9uIGV4aXN0aW5nIHJlY29yZHMgLyByb3dzIChlZy4gZHVwbG9jYXRlIHVzZXJuYW1lIGNoZWNrLCBidXQgZG9uJ3QgaW5jbHVkZSB0aGUgY3VycmVudCB1c2VyKS4KICovCnB1YmxpYyBmdW5jdGlvbiB2YWxpZGF0ZShhcnJheSAkZGF0YSA9IGFycmF5KCkpIAp7CgogICAgLy8gQWRkaXRpb25hbCB2YWxpZGF0aW9uIGNoZWNrcwoKfQoKfQoK', 
        'table' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflx0YWJsZTsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcbGliY1x7ZGIsIGRlYnVnfTsKCgovKioKICogSGFuZGxlcyB0aGUgdGFibGUgaW5jbHVkaW5nIG9idGFpbmluZyB0aGUgcm93cyB0byAKICogZGlzcGxheSwgdG90YWwgcm93cyBpbiB0aGUgdGFibGUsIGZvcm1hdHRpbmcgb2YgY2VsbHMsIGV0Yy4KICovCmNsYXNzIH5hbGlhc34KewoKICAgIC8vIENvbHVtbnMKICAgIHB1YmxpYyAkY29sdW1ucyA9IGFycmF5KAp+dGFibGVfY29sdW1uc34KICAgICk7CgogICAgLy8gU29ydGFibGUgY29sdW1ucwogICAgcHVibGljICRzb3J0YWJsZSA9IGFycmF5KCdpZCcpOwoKICAgIC8vIE90aGVyIHZhcmlhYmxlcwogICAgcHVibGljICRyb3dzX3Blcl9wYWdlID0gfnJvd3NfcGVyX3BhZ2V+OwogICAgcHVibGljICRoYXNfc2VhcmNoID0gfmhhc19zZWFyY2h+OwoKICAgIC8vIEZvcm0gZmllbGQgKGxlZnQtbW9zdCBjb2x1bW4pCiAgICBwdWJsaWMgJGZvcm1fZmllbGQgPSAnfmZvcm1fZmllbGR+JzsKICAgIHB1YmxpYyAkZm9ybV9uYW1lID0gJ35hbGlhc19zaW5nbGV+X2lkJzsKICAgIHB1YmxpYyAkZm9ybV92YWx1ZSA9ICdpZCc7IAoKfmRlbGV0ZV9idXR0b25fY29kZX4KCi8qKgogKiBQYXJzZSBhdHRyaWJ1dGVzIHdpdGhpbiA8YTpmdW5jdGlvbj4gdGFnLgogKgogKiBQYXNzZXMgdGhlIGF0dHJpYnV0ZXMgY29udGFpbmVkIHdpdGhpbiB0aGUgPGU6ZnVuY3Rpb24+IHRhZyB0aGF0IGNhbGxlZCB0aGUgdGFibGUuCiAqIFVzZWQgbWFpbmx5IHRvIHNob3cvaGlkZSBjb2x1bW5zLCBhbmQgcmV0cmlldmUgc3Vic2V0cyBvZiAKICogZGF0YSAoZWcuIHNwZWNpZmljIHJlY29yZHMgZm9yIGEgdXNlciBJRCMpLgogKiAKICggICAgIEBwYXJhbSBhcnJheSAkZGF0YSBUaGUgYXR0cmlidXRlcyBjb250YWluZWQgd2l0aGluIHRoZSA8ZTpmdW5jdGlvbj4gdGFnIHRoYXQgY2FsbGVkIHRoZSB0YWJsZS4KICovCnB1YmxpYyBmdW5jdGlvbiBnZXRfYXR0cmlidXRlcyhhcnJheSAkZGF0YSA9IGFycmF5KCkpCnsKCn0KCi8qKgogKiBHZXQgdG90YWwgcm93cy4KICoKICogR2V0IHRoZSB0b3RhbCBudW1iZXIgb2Ygcm93cyBhdmFpbGFibGUgZm9yIHRoaXMgdGFibGUuCiAqIFRoaXMgaXMgdXNlZCB0byBkZXRlcm1pbmUgcGFnaW5hdGlvbiBsaW5rcy4KICogCiAqICAgICBAcGFyYW0gc3RyaW5nICRzZWFyY2hfdGVybSBPbmx5IGFwcGxpY2FibGUgaWYgdGhlIEFKQVggc2VhcmNoIGJveCBoYXMgYmVlbiBzdWJtaXR0ZWQsIGFuZCBpcyB0aGUgdGVybSBiZWluZyBzZWFyY2hlZCBmb3IuCiAqICAgICBAcmV0dXJuIGludCBUaGUgdG90YWwgbnVtYmVyIG9mIHJvd3MgYXZhaWxhYmxlIGZvciB0aGlzIHRhYmxlLgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF90b3RhbChzdHJpbmcgJHNlYXJjaF90ZXJtID0gJycpOmludCAKewoKfmdldF90b3RhbF9jb2RlfgoKICAgIC8vIFJldHVybgogICAgcmV0dXJuIChpbnQpICR0b3RhbDsKCn0KCi8qKgogKiBHZXQgcm93cyB0byBkaXNwbGF5CiAqCiAqIEdldHMgdGhlIGFjdHVhbCByb3dzIHRvIGRpc3BsYXkgdG8gdGhlIHdlYiBicm93c2VyLgogKiBVc2VkIGZvciB3aGVuIGluaXRpYWxseSBkaXNwbGF5aW5nIHRoZSB0YWJsZSwgcGx1cyBBSkFYIGJhc2VkIHNlYXJjaCwgCiAqIHNvcnQsIGFuZCBwYWdpbmF0aW9uLgogKgogKiAgICAgQHBhcmFtIGludCAkc3RhcnQgVGhlIG51bWJlciB0byBzdGFydCByZXRyaWV2aW5nIHJvd3MgYXQsIHVzZWQgd2l0aGluIHRoZSBMSU1JVCBjbGF1c2Ugb2YgdGhlIFNRTCBzdGF0ZW1lbnQuCiAqICAgICBAcGFyYW0gc3RyaW5nICRzZWFyY2hfdGVybSBPbmx5IGFwcGxpY2FibGUgaWYgdGhlIEFKQVggYmFzZWQgc2VhcmNoIGJhc2UgaXMgc3VibWl0dGVkLCBhbmQgaXMgdGhlIHRlcm0gYmVpbmcgc2VhcmNoZWQgZm9ybS4KICogICAgIEBwYXJhbSBzdHJpbmcgJG9yZGVyX2J5IE11c3QgaGF2ZSBhIGRlZmF1bHQgdmFsdWUsIGJ1dCBjaGFuZ2VzIHdoZW4gdGhlIHNvcnQgYXJyb3dzIGluIGNvbHVtbiBoZWFkZXJzIGFyZSBjbGlja2VkLiAgVXNlZCB3aXRoaW4gdGhlIE9SREVSIEJZIGNsYXVzZSBpbiB0aGUgU1FMIHN0YXRlbWVudC4KICoKICogICAgIEByZXR1cm4gYXJyYXkgQW4gYXJyYXkgb2YgYXNzb2NpYXRpdmUgYXJyYXlzIGdpdmluZyBrZXktdmFsdWUgcGFpcnMgb2YgdGhlIHJvd3MgdG8gZGlzcGxheS4KICovCnB1YmxpYyBmdW5jdGlvbiBnZXRfcm93cyhpbnQgJHN0YXJ0ID0gMCwgc3RyaW5nICRzZWFyY2hfdGVybSA9ICcnLCBzdHJpbmcgJG9yZGVyX2J5ID0gJ2lkIGFzYycpOmFycmF5IAp7Cgp+Z2V0X3Jvd3NfY29kZX4KCiAgICAvLyBHbyB0aHJvdWdoIHJvd3MKICAgICRyZXN1bHRzID0gYXJyYXkoKTsKICAgIGZvcmVhY2ggKCRyb3dzIGFzICRyb3cpIHsgCiAgICAgICAgYXJyYXlfcHVzaCgkcmVzdWx0cywgJHRoaXMtPmZvcm1hdF9yb3coJHJvdykpOwogICAgfQoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRyZXN1bHRzOwoKfQoKLyoqCiAqIEZvcm1hdCBhIHNpbmdsZSByb3cuCiAqCiAqIFJldHJpZXZlcyByYXcgZGF0YSBmcm9tIHRoZSBkYXRhYmFzZSwgd2hpY2ggbXVzdCBiZSAKICogZm9ybWF0dGVkIGludG8gdXNlciByZWFkYWJsZSBmb3JtYXQgKGVnLiBmb3JtYXQgYW1vdW50cywgZGF0ZXMsIGV0Yy4pLgogKgogKiAgICAgQHBhcmFtIGFycmF5ICRyb3cgVGhlIHJvdyBmcm9tIHRoZSBkYXRhYmFzZS4KICoKICogICAgIEByZXR1cm4gYXJyYXkgVGhlIHJlc3VsdGluZyBhcnJheSB0aGF0IHNob3VsZCBiZSBkaXNwbGF5ZWQgdG8gdGhlIGJyb3dzZXIuCiAqLwpwdWJsaWMgZnVuY3Rpb24gZm9ybWF0X3JvdyhhcnJheSAkcm93KTphcnJheSAKewoKICAgIC8vIEZvcm1hdCByb3cKfmZvcm1hdF9saW5lc34KCiAgICAvLyBSZXR1cm4KICAgIHJldHVybiAkcm93OwoKfQoKfQoK', 
        'table_delete_button' => 'CiAgICAvLyBEZWxldGUgYnV0dG9uCiAgICBwdWJsaWMgJGRlbGV0ZV9idXR0b24gPSAnRGVsZXRlIENoZWNrZWQgfmFsaWFzX3BsdXJhbF90Y34nOwogICAgcHVibGljICRkZWxldGVfZGJ0YWJsZSA9ICd+ZGJ0YWJsZX4nOwogICAgcHVibGljICRkZWxldGVfZGJjb2x1bW4gPSAnaWQnOwoKCg==', 
        'table_get_total_search' => 'CiAgICAvLyBHZXQgdG90YWwKICAgIGlmICgkc2VhcmNoX3Rlcm0gIT0gJycpIHsgCiAgICAgICAgJHRvdGFsID0gREI6OmdldF9maWVsZCgiU0VMRUNUIGNvdW50KCopIEZST00gfmRidGFibGV+IFdIRVJFIH5zZWFyY2hfd2hlcmVfc3FsfiIsIH5zZWFyY2hfdmFyaWFibGVzfik7CiAgICB9IGVsc2UgeyAKICAgICAgICAkdG90YWwgPSBEQjo6Z2V0X2ZpZWxkKCJTRUxFQ1QgY291bnQoKikgRlJPTSB+ZGJ0YWJsZX4iKTsgICAKICAgIH0KICAgIGlmICgkdG90YWwgPT0gJycpIHsgJHRvdGFsID0gMDsgfQoK', 
        'table_get_total_nosearch' => 'CiAgICAvLyBHZXQgdG90YWwKICAgICR0b3RhbCA9IERCOjpnZXRfZmllbGQoIlNFTEVDVCBjb3VudCgqKSBGUk9NIH5kYnRhYmxlfiIpOyAgIAogICAgaWYgKCR0b3RhbCA9PSAnJykgeyAkdG90YWwgPSAwOyB9Cgo=', 
        'table_get_rows_search' => 'CiAgICAvLyBHZXQgcm93cwogICAgaWYgKCRzZWFyY2hfdGVybSAhPSAnJykgeyAKICAgICAgICAkcm93cyA9IERCOjpxdWVyeSgiU0VMRUNUICogRlJPTSB+ZGJ0YWJsZX4gV0hFUkUgfnNlYXJjaF93aGVyZV9zcWx+IE9SREVSIEJZICRvcmRlcl9ieSBMSU1JVCAkc3RhcnQsJHRoaXMtPnJvd3NfcGVyX3BhZ2UiLCB+c2VhcmNoX3ZhcmlhYmxlc34pOwogICAgfSBlbHNlIHsgCiAgICAgICAgJHJvd3MgPSBEQjo6cXVlcnkoIlNFTEVDVCAqIEZST00gfmRidGFibGV+IE9SREVSIEJZICRvcmRlcl9ieSBMSU1JVCAkc3RhcnQsJHRoaXMtPnJvd3NfcGVyX3BhZ2UiKTsKICAgIH0KCgo=', 
        'table_get_rows_nosearch' => 'CiAgICAvLyBHZXQgcm93cwogICAgJHJvd3MgPSBEQjo6cXVlcnkoIlNFTEVDVCAqIEZST00gfmRidGFibGV+IE9SREVSIEJZICRvcmRlcl9ieSBMSU1JVCAkc3RhcnQsJHRoaXMtPnJvd3NfcGVyX3BhZ2UiKTsKCgo=', 
        'view_admin_main_tpl' => 'CjxoMT5+YWxpYXNfcGx1cmFsX3RjfjwvaDE+Cgo8YTpib3g+CiAgICA8YTpib3hfaGVhZGVyIHRpdGxlPSJFeGlzdGluZyB+YWxpYXNfcGx1cmFsX3RjfiI+CiAgICAgICAgPHA+VGhlIGJlbG93IHRhYmxlIGxpc3RzIGFsbCBleGlzdGluZyB+YWxpYXNfcGx1cmFsfiB3aGljaCB5b3UgbWF5IG1hbmFnZSBvciBkZWxldGUuPC9wPgogICAgPC9hOmJveF9oZWFkZXI+CgogICAgPGE6Zm9ybT4KICAgIDxhOmZ1bmN0aW9uIGFsaWFzPSJkaXNwbGF5X3RhYmxlIiB0YWJsZT0ifnBhY2thZ2V+On5hbGlhc19wbHVyYWx+Ij4KICAgIDwvZm9ybT4KPC9hOmJveD4KCjxhOmJveD4KICAgIDxhOmJveF9oZWFkZXIgdGl0bGU9IkNyZWF0ZSBOZXcgfmFsaWFzX3RjfiI+CiAgICAgICAgPHA+WW91IG1heSBjcmVhdGUgYSBuZXcgfmFsaWFzfiBieSBjb21wbGV0aW5nIHRoZSBiZWxvdyBmb3JtLjwvcD4KICAgIDwvYTpib3hfaGVhZGVyPgoKICAgIDxhOmZvcm0+CiAgICA8YTpmdW5jdGlvbiBhbGlhcz0iZGlzcGxheV9mb3JtIiBmb3JtPSJ+cGFja2FnZX46fmFsaWFzfiI+CiAgICA8L2Zvcm0+CjwvYTpib3g+CgoKCg==', 
        'view_admin_main_php' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XHZpZXdzOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZm9ybXMsIGRlYnVnLCB2aWV3fTsKCi8qKgogKiBBbGwgY29kZSBiZWxvdyB0aGlzIGxpbmUgaXMgYXV0b21hdGljYWxseSBleGVjdXRlZCB3aGVuIHRoaXMgdGVtcGxhdGUgaXMgdmlld2VkLCAKICogYW5kIHVzZWQgdG8gcGVyZm9ybSBhbnkgbmVjZXNzYXJ5IHRlbXBsYXRlIHNwZWNpZmljIGFjdGlvbnMuCiAqLwoKLy8gQ3JlYXRlIG5ldyB+YWxpYXN+CmlmIChhcHA6OmdldF9hY3Rpb24oKSA9PSAnY3JlYXRlJykgeyAKCiAgICAvLyBWYWxpZGF0ZSBmb3JtCiAgICBpZiAoIWZvcm1zOjp2YWxpZGF0ZV9mb3JtKCd+cGFja2FnZX46fmFsaWFzficpKSB7IAogICAgICAgIHJldHVybjsKICAgIH0KCiAgICAvLyBBZGQgdG8gZGF0YWJhc2UKICAgIGRiOjppbnNlcnQoJ35kYnRhYmxlficsIGFycmF5KAp+cGhwX2NvZGV+KQogICAgKTsKCiAgICAvLyBBZGQgY2FsbG91dAogICAgdmlldzo6YWRkX2NhbGxvdXQoJ1N1Y2Nlc3NmdWxseSBjcmVhdGVkIG5ldyB+YWxpYXN+Jyk7CgovLyBVcGRhdGUgfmFsaWFzfgp9IGVsc2VpZiAoYXBwOjpnZXRfYWN0aW9uKCkgPT0gJ3VwZGF0ZScpIHsgCgogICAgLy8gVmFsaWRhdGUgZm9ybQogICAgaWYgKCFmb3Jtczo6dmFsaWRhdGVfZm9ybSgnfnBhY2thZ2V+On5hbGlhc34nKSkgeyAKICAgICAgICByZXR1cm47CiAgICB9CgogICAgLy8gVXBkYXRlIGRhdGFic2FlCiAgICBkYjo6dXBkYXRlKCd+ZGJ0YWJsZX4nLCBhcnJheSgKfnBocF9jb2RlfiksIAogICAgJ2lkID0gJWknLCBhcHA6Ol9wb3N0KCdyZWNvcmRfaWQnKSk7CgogICAgLy8gQWRkIGNhbGxvdXQKICAgIHZpZXc6OmFkZF9jYWxsb3V0KCdTdWNjZXNzZnVsbHkgdXBkYXRlZCB0aGUgYXBwcm9wcmlhdGUgfmFsaWFzficpOwoKfQoKCg==', 
        'view_admin_manage_tpl' => 'CixoMT5NYW5hZ2UgfmFsaWFzX3RjfjwvaDE+Cgo8YTpmb3JtIGFjdGlvbj0ifnVyaX4iPiAKPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0icmVjb3JkX2lkIiB2YWx1ZT0ifnJlY29yZF9pZH4iPgoKPGE6Ym94PgogICAgPGE6Ym94X2hlYWRlciB0aXRsZT0ifmFsaWFzX3VjfiBEZXRhaWxzIj4KICAgICAgICA8cD5Zb3UgbWF5IG1vZGlmeSB0aGUgc2VsZWN0ZWQgfmFsaWFzfiBieSBtYWtkaW5nIHRoZSBkZXNpcmVkIGNoYW5nZXMgdG8gdGhlIGluZm9ybWF0aW9uIGJlbG93LjwvcD4KICAgIDwvYTpib3hfaGVhZGVyPgoKICAgIDxhOmZ1bmN0aW9uIGFsaWFzPSJkaXNwbGF5X2Zvcm0iIGZvcm09In5wYWNrYWdlfjp+YWxpYXN+IiByZWNvcmRfaWQ9In5yZWNvcmRfaWR+Ij4KPC9hOmJveD4KCgoK', 
        'view_admin_manage_php' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XHZpZXdzOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZGVidWcsIHZpZXd9OwoKLyoqCiAqIEFsbCBjb2RlIGJlbG93IHRoaXMgbGluZSBpcyBhdXRvbWF0aWNhbGx5IGV4ZWN1dGVkIHdoZW4gdGhpcyB0ZW1wbGF0ZSBpcyB2aWV3ZWQsIAogKiBhbmQgdXNlZCB0byBwZXJmb3JtIGFueSBuZWNlc3NhcnkgdGVtcGxhdGUgc3BlY2lmaWMgYWN0aW9ucy4KICovCgoKLy8gVGVtcGxhdGUgdmFyaWFibGVzCnZpZXc6OmFzc2lnbigncmVjb3JkX2lkJywgYXBwOjpfZ2V0KCdyZWNvcmRfaWQnKSk7CgoK'
    ];

/**
 * Create CRUD operations
 *
 * Takes in the crud.yaml file from the installation 
 * directory, and creates the necessary views, data table, 
 * and form components to handle CRUD operations for the 
 * given database table.
 *
 * @param string $file Full path to the crud.yaml file to use.
 *
 * @return array Three elements, the alias, package, and an array of all newly created files.
 */
public function create(string $file):array
{

    // Load YAML file
    try {
        $vars = Yaml::parseFile($file);
    } catch (ParseException $e) { 
        throw new ApexException('error', tr("Unable to parse YAML file for CRUD creation.  Message: {1}", $e->getMessage()));
    }

    // Set variables
    $this->package = $vars['package'] ?? '';
    $this->dbtable = $vars['dbtable'] ?? '';
    $this->alias = $vars['alias'] ?? preg_replace("/^" . $this->package . "_/", "", $this->dbtable);
    $views = $vars['views'] ?? [];
    $admin_uri = $views['admin'] ?? '';

    // Get singular alias
    $inflector = new EnglishInflector();
    $single = $inflector->singularize($this->alias);
    $this->alias = is_array($single) ? $single[0] : $single;

    // Get plural alias
    $plural = $inflector->pluralize($this->alias);
    $this->alias_plural = is_array($plural) ? $plural[0] : $plural;

    // Perform checks
    $this->perform_checks();

    // Get columns
    $this->columns = db::show_columns($this->dbtable, true);
    if (isset($this->columns['id'])) { unset($this->columns['id']); }

    // Create form
    $form = $vars['form'] ?? [];
    $this->create_form($form);

    // Create table
    $table = $vars['table'] ?? [];
    $this->create_table($table, $admin_uri);

    // Create admin view
    if ($admin_uri != '') { 
        $form_exclude = $form['exclude'] ?? [];
        $this->create_view_admin($admin_uri, $form_exclude);
    }

    // Return
    return array($this->alias, $this->package, $this->created_files);

}

/**
 * Perform checks
 */
private function perform_checks()
{

    // Ensure database table exists
    $tables = db::show_tables();
    if (!in_array($this->dbtable, $tables)) { 
        throw new ApexException('error', tr("The database table does not exist, {1}", $this->dbtable));
    }

    // Ensure package exists
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $this->package)) {
        throw new PackageException('not_exists', $this->package);
    }

    // Check alias
    if ($this->alias == '' || preg_match("/[\W\s]/", $this->alias)) { 
        throw new ApexException('error', tr("Invalid alias defined, {1}", $this->alias));
    }

    // Check if components exist
    if (components::check('form', $this->package . ':' . $this->alias)) { 
        throw new ComponentException('already_exists', 'form', $this->package . ':' . $this->alias);
    } elseif (components::check('table', $this->package . ':' . $this->alias_plural)) { 
        throw new ComponentException('already_exists', 'table', $this->package . ':' . $this->alias_plural);
    }

        // Check admin views, if needed
    if (isset($views['admin']) && $views['admin'] != '') { 
        if (components::check('view', $views['admin'])) { 
            throw new ComponentException('already_exists', 'view', $this->package . ':' . $views['admin']);
        } elseif (components::check('view', $views['admin'] . '_manage')) { 
            throw new ComponentException('already_exists', 'view', $this->package . ':' . $views['admin'] . '_manage');
        }
    }

}


/**
 * Create form component
 *
 * @param array $form The 'form' array from the YAML file, if one exists.
 */
private function create_form(array $form)
{

    // Set form variables
    $exclude = isset($form['exclude']) ? explode(',', $form['exclude']) : [];

    // Get form fields
    $form_fields = [];
    foreach ($this->columns as $alias => $type) {
        if (in_array($alias, $exclude)) { continue; }
        if (preg_match("/^(date|datetime|timestamp)/i", $type)) { continue; }
        $fld = $form[$alias] ?? [];

        // Get field type
        if (isset($fld['field'])) { $field = $fld['field']; }
        elseif (preg_match("/^DECIMAL/i", $type)) { $field = 'amount'; }
        elseif (preg_match("/^tinyint/i", $type)) { $field = 'boolean'; }
        elseif (preg_match("/text$/", $type)) { $field = 'textarea'; }
        else { $field = 'textbox'; }

        // Set PHP line
        $line = "        '$alias' => ['field' => '$field'";
        if (isset($fld['label'])) { $line .= ", 'label' => '$fld[label]'"; }
        if (isset($fld['data_source'])) { $line .= ", 'data_source' => '$fld[data_source]'"; }
        if (isset($fld['required']) && $fld['required'] == 1) { $line .= ", 'required' => 1"; }
        if (isset($fld['width'])) { $line .= ", 'width' => '$fld[width]'"; }
        if (isset($fld['value'])) { $line .= ", 'value' => '$fld[value]'"; }
        if (isset($fld['placeholder'])) { $line .= ", 'placeholder' => '$fld[placeholder]'"; }
        $line .= "]";

        // Add to fields
        $form_fields[] = $line;
    }

    // Create the component
    $comp_alias = $this->package . ':' . $this->alias;
    pkg_component::create('form', $comp_alias, $this->package);

    // Save form.php file
    $code = base64_decode($this->code_templates['form']);
    $code = str_replace("~form_fields~", implode(", \n", $form_fields), $code);
    $code = str_replace("~alias_single_tc~", ucwords($this->alias), $code);
    $code = str_replace("~dbtable~", $this->dbtable, $code);
    $code = str_replace("~package~", $this->package, $code);
    $code = str_replace("~alias~", $this->alias, $code);
    file_put_contents(SITE_PATH . '/src/' . $this->package . '/form/' . $this->alias . '.php', $code);

    // Get created files
    $files = components::get_all_files('form', $this->alias, $this->package);
    $this->created_files = array_merge($this->created_files, $files);

}

/**
 * Create data table
 *
 * @param array $table The 'table' array from the YAML file, if exists
 * @param string $manage_uri The optional URI of the manage view, if a manage button is included with table.
 */
private     function create_table(array $table, string $manage_uri = '')
{

    // Set variables
    $exclude = isset($table['exclude']) ? explode(',', $table['exclude']) : [];
    $manage_button = $table['manage_button'] ?? 0;
    $delete_button = $table['delete_button'] ?? 1;
    $rows_per_page = $table['rows_per_page'] ?? 25;
    $form_field = $table['form_field'] ?? 'checkbox';
    $delete_button_code = $delete_button == 1 ? base64_decode($this->code_templates['table_delete_button']) : '';

    // Get table columns / format lines
    list($table_columns, $format_lines) = $this->table_get_columns((int) $manage_button, $manage_uri, $exclude);

    // Get search table variables
    $has_search = isset($table['has_search']) && $table['has_search'] == 1 ? 'true' : 'false';
    $search_columns = isset($table['search_columns']) ? explode(',', $table['search_columns']) : [];
    if ($has_search === 'true' && count($search_columns) > 0) { 

        $tmp = [];
        for ($x=1; $x <= count($search_columns); $x++) { $tmp[] = "\$search_term"; }

        $search_variables = implode(', ', $tmp);
        $search_where_sql = implode(" LIKE %ls OR ", $search_columns) . ' LIKE %ls';
        $get_total_code = base64_decode($this->code_templates['table_get_total_search']);
        $get_rows_code = base64_decode($this->code_templates['table_get_rows_search']);

    } else { 
        $search_variables = '';
        $search_where_sql = '';
        $get_total_code = base64_decode($this->code_templates['table_get_total_nosearch']);
        $get_rows_code = base64_decode($this->code_templates['table_get_rows_nosearch']);
    }

    // Create the component
    $comp_alias = $this->package . ':' . $this->alias_plural;
    pkg_component::create('table', $comp_alias, $this->package);

    // Save code
    $code = base64_decode($this->code_templates['table']);
    $code = str_replace("~table_columns~", implode(", \n", $table_columns), $code);
    $code = str_replace("~format_lines~", implode("\n", $format_lines), $code);
    $code = str_replace("~rows_per_page~", $rows_per_page, $code);
    $code = str_replace("~has_search~", $has_search, $code);
    $code = str_replace("~form_field~", $form_field, $code);
    $code = str_replace("~delete_button_code~", $delete_button_code, $code);
    $code = str_replace("~get_total_code~", $get_total_code, $code);
    $code = str_replace("~get_rows_code~", $get_rows_code, $code);

    // Replace alias & package variables in code
    $code = str_replace("~alias~", $this->alias_plural, $code);
    $code = str_replace("~package~", $this->package, $code);
    $code = str_replace("~dbtable~", $this->dbtable, $code);
    $code = str_replace("~alias_single~", $this->alias, $code);
    $code = str_replace("~alias_plural_tc~", ucwords($this->alias_plural), $code);
    $code = str_replace("~search_where_sql~", $search_where_sql, $code);
    $code = str_replace("~search_variables~", $search_variables, $code);

    // Save code file
        $file = SITE_PATH . '/src/' . $this->package . '/table/' . $this->alias_plural . '.php';
    file_put_contents($file, $code);

    // Get created files
    $files = components::get_all_files('table', $this->alias_plural, $this->package);
    $this->created_files = array_merge($this->created_files, $files);

}

/*8
 * Table - get columns and format lines
 * 
* @param int $manage_button A 1 / 0 defining whether or not to add 'Mangae' button to columns
 * @param string $manage_uri The manage URI used within the manage button.
 * @param array $exclude Optional array of database columns to exclude from the table.
 * 
 * @return array Two element array, the table columns and format lines.
 */
private function table_get_columns(int $manage_button = 0, string $manage_uri = '', array $exclude = [])
{

    // Initialize
    $table_columns = [];
    $format_lines = [];

    // Get table columns
    foreach ($this->columns as $alias => $type) { 
        if (in_array($alias, $exclude)) { continue; }
        $table_columns[] = "        '$alias' => '" . ucwords(str_replace('_', ' ', $alias)) . "'";

        // Add format line, if needed
        if (preg_match("/^decimal/i", $type)) { 
            $format_lines[] = "    \$row['$alias'] = fmoney(\$row['$alias']);";
        } elseif (preg_match("/^(datetime|timestamp)/i", $type)) { 
            $format_lines[] = "    \$row['$alias'] = fdate(\$row['$alias'], true);";
        } elseif (preg_match("/^date/i", $type)) { 
            $format_lines[] = "    \$row['$alias'] = fdate(\$row['$alias']);";
        } elseif (preg_match("/^tinyint/i", $type)) { 
            $format_lines[] = "    \$row['$alias'] = \$row['$alias'] == 1 ? 'Yes' : 'No';";
        }
    }

    // Add manage button, if needed
    if ($manage_button == 1) { 
        $table_columns[] = "        'manage' => 'Manage'";
        $format_lines[] = "    \$row['manage'] = \"<center><a:button href=\\\"" . $manage_uri . "_manage?record_id=\$row[id]\\\" label=\\\"Manage\\\"></center>\";";
    }

    // Return
    return array($table_columns, $format_lines);

}

/**
 * Create admin main view
 *
 * @param string $uri The URI of the view filename
 * @param array $exclude The 'form'->'exclude' array from the YML file, if exists.
 */
public function create_view_admin(string $uri, array $exclude = [])
{

    // Create main view components
    pkg_component::create('view', $uri, $this->package);
    pkg_component::create('view', $uri . '_manage', $this->package);

    // Get .tpl / .php code
    $code = [
        'tpl/' . $uri . '.tpl' => base64_decode($this->code_templates['view_admin_main_tpl']), 
        'php/' . $uri . '.php' => base64_decode($this->code_templates['view_admin_main_php']), 
        'tpl/' . $uri . '_manage.tpl' => base64_decode($this->code_templates['view_admin_manage_tpl']), 
        'php/' . $uri . '_manage.php' => base64_decode($this->code_templates['view_admin_manage_php'])
    ];

    // Set create php code
    $php_code = [];
    foreach ($this->columns as $alias => $type) { 
        if (in_array($alias, $exclude)) { continue; }
        if (preg_match("/^(date|timestamp)/i", $type)) { continue; }
        $php_code[] = "        '$alias' => app::_post('$alias')";
    }

    // Set merge variables
    $merge_vars = [
        'package' => $this->package, 
        'alias' => $this->alias, 
        'alias_tc' => ucwords($this->alias),  
        'alias_plural' => $this->alias_plural, 
        'alias_plural_tc' => ucwords($this->alias_plural), 
        'dbtable' => $this->dbtable,
        'uri' => $uri, 
        'php_code' => implode(", \n", $php_code)
    ];

    // Replace merge fields
    foreach ($code as $file => $contents) { 

        foreach ($merge_vars as $key => $value) { 
            $code[$file] = str_replace("~$key~", $value, $code[$file]);
        }

        // Save file
        file_put_contents(SITE_PATH . '/views/' . $file, $code[$file]);
    }

    // Get created files
    $this->created_files = array_merge($this->created_files, components::get_all_files('view', $uri, $this->package));
    $this->created_files = array_merge($this->created_files, components::get_all_files('view', $uri . '_manage', $this->package));

}


}


