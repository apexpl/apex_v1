<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\libc\{db, redis, debug, components, io, date};
use apex\app\pkg\package_config;
use apex\app\exceptions\{ApexException, ComponentException};



/**
 * Handles creating, adding, deleting, and updating components within 
 * packages.  Used for all package / upgrade functions. 
 */
class pkg_component
{

    // Set code templates
    private static $code_templates = [
        'adapter' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxzZXJ2aWNlXH5wYXJlbnR+OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZGVidWd9Owp1c2UgYXBleFx+cGFja2FnZX5cc2VydmljZVx+cGFyZW50fjsKCi8qKgogKiBBZGFwYXRlciBmb3IgdGhlIHNlcnZpY2UsIGFuZCBoYW5kbGVzIGFsbCBtZXRob2RzIGFzIHByZXNjcmliZWQgCiAqIGJ5IHRoZSBzZXJ2aWNlJ3MgYWJzdHJhY3QgY2xhc3MuCiAqLwpjbGFzcyB+YWxpYXN+IGV4dGVuZHMgfnBhcmVudH4KewoKIC8qKgogICAgICogQmxhbmsgUEhQIGNsYXNzIGZvciB0aGUgYWRhcHRlci4gIEZvciB0aGUgCiAgICAgKiBjb3JyZWN0IG1ldGhvZHMgYW5kIHByb3BlcnRpZXMgZm9yIHRoaXMgY2xhc3MsIHBsZWFzZSAKICAgICAqIHJldmlldyB0aGUgYWJzdHJhY3QgY2xhc3MgbG9jYXRlZCBhdDoKICAgICAqICAgICAvc3JjL35wYWNrYWdlfi9zZXJ2aWNlL35wYXJlbnR+LnBocAogICAgICovCgoKfQoK', 
        'ajax' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxhamF4OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZGVidWd9Owp1c2UgYXBleFxhcHBcd2ViXGFqYXg7CgovKioKICogSGFuZGxlcyB0aGUgQUpBWCBmdW5jdGlvbiBvZiB0aGlzIGNsYXNzLCBhbGxvd2luZyAKICogRE9NIGVsZW1lbnRzIHdpdGhpbiB0aGUgYnJvd3NlciB0byBiZSBtb2RpZmllZCBpbiByZWFsLXRpbWUgCiAqIHdpdGhvdXQgaGF2aW5nIHRvIHJlZnJlc2ggdGhlIGJyb3dzZXIuCiAqLwpjbGFzcyB+YWxpYXN+IGV4dGVuZHMgYWpheAp7CgovKioKICAgICogUHJvY2Vzc2VzIHRoZSBBSkFYIGZ1bmN0aW9uLgogKgogKiBQcm9jZXNzZXMgdGhlIEFKQVggZnVuY3Rpb24sIGFuZCB1c2VzIHRoZSAKICogbW9ldGhkcyB3aXRoaW4gdGhlICdhcGV4XGFqYXgnIGNsYXNzIHRvIG1vZGlmeSB0aGUgCiAqIERPTSBlbGVtZW50cyB3aXRoaW4gdGhlIHdlYiBicm93c2VyLiAgU2VlIAogKiBkb2N1bWVudGF0aW9uIGZvciBkdXJ0aGVyIGRldGFpbHMuCiAqLwpwdWJsaWMgZnVuY3Rpb24gcHJvY2VzcygpCnsKCiAgICAvLyBQZXJmb3JtIG5lY2Vzc2FyeSBhY3Rpb25zCgp9Cgp9Cgo=', 
        'autosuggest' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxhdXRvc3VnZ2VzdDsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcbGliY1x7ZGIsIGRlYnVnfTsKCgovKioKICogVGhlIGF1dG8tc3VnZ2VzdCBjbGFzcyB0aGF0IGFsbG93cyBkaXNwbGF5aW5nIG9mIAogKiBkcm9wIGRvd24gbGlzdHMgdGhhdCBhcmUgYXV0b21hdGljYWxseSBmaWxsZWQgd2l0aCBzdWdnZXN0aW9ucy4KICovCmNsYXNzIH5hbGlhc34KewoKLyoqCiAgICAqIFNlYXJjaCBhbmQgZGV0ZXJtaW5lIHN1Z2dlc3Rpb25zLgogKgogKiBTZWFyY2hlcyBkYXRhYmFzZSB1c2luZyB0aGUgZ2l2ZW4gJHRlcm0sIGFuZCByZXR1cm5zIGFycmF5IG9mIHJlc3VsdHMsIHdoaWNoIAogKiBhcmUgdGhlbiBkaXNwbGF5ZWQgd2l0aGluIHRoZSBhdXRvLXN1Z2dlc3QgLyBjb21wbGV0ZSBib3guCiAqCiAqICAgICBAcGFyYW0gc3RyaW5nICR0ZXJtIFRoZSBzZWFyY2ggdGVybSBlbnRlcmVkIGludG8gdGhlIHRleHRib3guCiAqICAgICBAcmV0dXJuIGFycmF5IEFuIGFycmF5IG9mIGtleS12YWx1ZSBwYXJpcywga2V5cyBhcmUgdGhlIHVuaXF1ZSBJRCMgb2YgdGhlIHJlY29yZCwgYW5kIHZhbHVlcyBhcmUgZGlzcGxheWVkIGluIHRoZSBhdXRvLXN1Z2dlc3QgbGlzdC4KICovCnB1YmxpYyBmdW5jdGlvbiBzZWFyY2goc3RyaW5nICR0ZXJtKTphcnJheSAKewoKICAgIC8vIEdldCBvcHRpb25zCiAgICAkb3B0aW9ucyA9IGFycmF5KCk7CgoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRvcHRpb25zOwoKfQoKfQoK', 
        'cli' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxjbGk7Cgp1c2UgYXBleFxhcHA7CnVzZSBhcGV4XGxpYmNce2RiLCBkZWJ1Z307CgovKioKICogQ2xhc3MgdG8gaGFuZGxlIHRoZSBjdXN0b20gQ0xJIGNvbW1hbmQgCiAqIGFzIG5lY2Vzc2FyeS4KICovCmNsYXNzIH5hbGlhc34KewoKLyoqCiAqIEV4ZWN1dGVzIHRoZSBDTEkgY29tbWFuZC4KICogIAogKiAgICAgQHBhcmFtIGl0ZXJhYmxlICRhcmdzIFRoZSBhcmd1bWVudHMgcGFzc2VkIHRvIHRoZSBjb21tYW5kIGNsaW5lLgogKi8KcHVibGljIGZ1bmN0aW9uIHByb2Nlc3MoLi4uJGFyZ3MpCnsKCgoKfQoKfQoK', 
        'cron' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxjcm9uOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZGVidWd9OwoKLyoqCiAqIENsYXNzIHRoYXQgYW5kbGVzIHRoZSBjcm9udGFiIGpvYi4KICovCmNsYXNzIH5hbGlhc34KewoKICAgIC8vIFByb3BlcnRpZXMKICAgIHB1YmxpYyAkYXV0b3J1biA9IDE7CiAgICBwdWJsaWMgJHRpbWVfaW50ZXJ2YWwgPSAnSTMwJzsKICAgIHB1YmxpYyAkbmFtZSA9ICd+YWxpYXN+JzsKCi8qKgogKiBQcm9jZXNzZXMgdGhlIGNyb250YWIgam9iLgogKi8KcHVibGljIGZ1bmN0aW9uIHByb2Nlc3MoKQp7CgoKCn0KCn0K', 
        'form' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxmb3JtOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZGVidWd9OwoKCi8qKiAKICogQ2xhc3MgZm9yIHRoZSBIVE1MIGZvcm0gdG8gZWFzaWx5IGRlc2lnbiBmdWxseSBmdW5jdGlvbmFsIAogKiBmb3JtcyB3aXRoIGJvdGgsIEphdmFzY3JpcHQgYW5kIHNlcnZlci1zaWRlIHZhbGlkYXRpb24uCiAqLwpjbGFzcyB+YWxpYXN+CnsKCiAgICAvLyBQcm9wZXJ0aWVzCiAgICBwdWJsaWMgJGFsbG93X3Bvc3RfdmFsdWVzID0gMTsKCi8qKgogKiBEZWZpbmVzIHRoZSBmb3JtIGZpZWxkcyBpbmNsdWRlZCB3aXRoaW4gdGhlIEhUTUwgZm9ybS4KICogCiAqICAgQHBhcmFtIGFycmF5ICRkYXRhIEFuIGFycmF5IG9mIGFsbCBhdHRyaWJ1dGVzIHNwZWNpZmllZCB3aXRoaW4gdGhlIGU6ZnVuY3Rpb24gdGFnIHRoYXQgY2FsbGVkIHRoZSBmb3JtLiAKICoKICogICBAcmV0dXJuIGFycmF5IEtleXMgb2YgdGhlIGFycmF5IGFyZSB0aGUgbmFtZXMgb2YgdGhlIGZvcm0gZmllbGRzLiAgVmFsdWVzIG9mIHRoZSBhcnJheSBhcmUgYXJyYXlzIHRoYXQgc3BlY2lmeSB0aGUgYXR0cmlidXRlcyBvZiB0aGUgZm9ybSBmaWVsZC4gIFJlZmVyIHRvIGRvY3VtZW50YXRpb24gZm9yIGRldGFpbHMuCiAqLwpwdWJsaWMgZnVuY3Rpb24gZ2V0X2ZpZWxkcyhhcnJheSAkZGF0YSA9IGFycmF5KCkpOmFycmF5CnsKCiAgICAvLyBTZXQgZm9ybSBmaWVsZHMKICAgICRmb3JtX2ZpZWxkcyA9IGFycmF5KCAKICAgICAgICAnbmFtZScgPT4gYXJyYXkoJ2ZpZWxkJyA9PiAndGV4dGJveCcsICdsYWJlbCcgPT4gJ1lvdXIgRnVsbCBOYW1lJywgJ3JlcXVpcmVkJyA9PiAxLCAncGxhY2Vob2xkZXInID0+ICdFbnRlciB5b3VyIG5hbWUuLi4nKQogICAgKTsKCgogICAgLy8gQWRkIHN1Ym1pdCBidXR0b24KICAgIGlmIChpc3NldCgkZGF0YVsncmVjb3JkX2lkJ10pICYmICRkYXRhWydyZWNvcmRfaWQnXSA+IDApIHsgCiAgICAgICAgJGZvcm1fZmllbGRzWydzdWJtaXQnXSA9IGFycmF5KCdmaWVsZCcgPT4gJ3N1Ym1pdCcsICd2YWx1ZScgPT4gJ3VwZGF0ZScsICdsYWJlbCcgPT4gJ1VwZGF0ZSBSZWNvcmQnKTsKICAgIH0gZWxzZSB7IAogICAgICAgICRmb3JtX2ZpZWxkc1snc3VibWl0J10gPSBhcnJheSgnZmllbGQnID0+ICdzdWJtaXQnLCAndmFsdWUnID0+ICdjcmVhdGUnLCAnbGFiZWwnID0+ICdDcmVhdGUgTmV3IFJlY29yZCcpOwogICAgfQoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRmb3JtX2ZpZWxkczsKCn0KCi8qKgogKiBHZXQgdmFsdWVzIGZvciBhIHJlY29yZC4KICoKICogTWV0aG9kIGlzIGNhbGxlZCBpZiBhICdyZWNvcmRfaWQnIGF0dHJpYnV0ZSBleGlzdHMgd2l0aGluIHRoZSAKICogYTpmdW5jdGlvbiB0YWcgdGhhdCBjYWxscyB0aGUgZm9ybS4gIFdpbGwgcmV0cmlldmUgdGhlIHZhbHVlcyBmcm9tIHRoZSAKICogZGF0YWJhc2UgdG8gcG9wdWxhdGUgdGhlIGZvcm0gZmllbGRzIHdpdGguCiAqCiAqICAgQHBhcmFtIHN0cmluZyAkcmVjb3JkX2lkIFRoZSB2YWx1ZSBvZiB0aGUgJ3JlY29yZF9pZCcgYXR0cmlidXRlIGZyb20gdGhlIGU6ZnVuY3Rpb24gdGFnLgogKgogKiAgIEByZXR1cm4gYXJyYXkgQW4gYXJyYXkgb2Yga2V5LXZhbHVlIHBhaXJzIGNvbnRhaW5nIHRoZSB2YWx1ZXMgb2YgdGhlIGZvcm0gZmllbGRzLgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9yZWNvcmQoc3RyaW5nICRyZWNvcmRfaWQpOmFycmF5IAp7CgogICAgLy8gR2V0IHJlY29yZAogICAgJHJvdyA9IGFycmF5KCk7CgogICAgLy8gUmV0dXJuCiAgICByZXR1cm4gJHJvdzsKCn0KCi8qKgogKiBBZGRpdGlvbmFsIGZvcm0gdmFsaWRhdGlvbi4KICogCiAqIEFsbG93cyBmb3IgYWRkaXRpb25hbCB2YWxpZGF0aW9uIG9mIHRoZSBzdWJtaXR0ZWQgZm9ybS4gIAogKiBUaGUgc3RhbmRhcmQgc2VydmVyLXNpZGUgdmFsaWRhdGlvbiBjaGVja3MgYXJlIGNhcnJpZWQgb3V0LCBhdXRvbWF0aWNhbGx5IGFzIAogKiBkZXNpZ25hdGVkIGluIHRoZSAkZm9ybV9maWVsZHMgZGVmaW5lZCBmb3IgdGhpcyBmb3JtLiAgSG93ZXZlciwgdGhpcyAKICogYWxsb3dzIGFkZGl0aW9uYWwgdmFsaWRhdGlvbiBpZiB3YXJyYW50ZWQuCiAqCiAqICAgICBAcGFyYW0gYXJyYXkgJGRhdGEgQW55IGFycmF5IG9mIGRhdGEgcGFzc2VkIHRvIHRoZSByZWdpc3RyeTo6dmFsaWRhdGVfZm9ybSgpIG1ldGhvZC4gIFVzZWQgdG8gdmFsaWRhdGUgYmFzZWQgb24gZXhpc3RpbmcgcmVjb3JkcyAvIHJvd3MgKGVnLiBkdXBsb2NhdGUgdXNlcm5hbWUgY2hlY2ssIGJ1dCBkb24ndCBpbmNsdWRlIHRoZSBjdXJyZW50IHVzZXIpLgogKi8KcHVibGljIGZ1bmN0aW9uIHZhbGlkYXRlKGFycmF5ICRkYXRhID0gYXJyYXkoKSkgCnsKCiAgICAvLyBBZGRpdGlvbmFsIHZhbGlkYXRpb24gY2hlY2tzCgp9Cgp9Cgo=', 
        'htmlfunc' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxodG1sZnVuYzsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcbGliY1x7ZGIsIGRlYnVnfTsKCi8qKgogKiBDbGFzcyB0byBoYW5kbGUgdGhlIEhUTUwgZnVuY3Rpb24sIHdoaWNoIHJlcGxhY2VzIAogKiB0aGUgPGE6ZnVuY3Rpb24+IHRhZ3Mgd2l0aGluIHRlbXBsYXRlcyB0byBhbnl0aGluZyAKICogeW91IHdpc2guCiAqLwpjbGFzcyB+YWxpYXN+CnsKCi8qKgogKiBSZXBsYWNlcyB0aGUgY2FsbGluZyA8ZTpmdW5jdGlvbj4gdGFnIHdpdGggdGhlIHJlc3VsdGluZyAKICogc3RyaW5nIG9mIHRoaXMgZnVuY3Rpb24uCiAqIAogKiAgIEBwYXJhbSBzdHJpbmcgJGh0bWwgVGhlIGNvbnRlbnRzIG9mIHRoZSBUUEwgZmlsZSwgaWYgZXhpc3RzLCBsb2NhdGVkIGF0IC92aWV3cy9odG1sZnVuYy88cGFja2FnZT4vPGFsaWFzPi50cGwKICogICBAcGFyYW0gYXJyYXkgJGRhdGEgVGhlIGF0dHJpYnV0ZXMgd2l0aGluIHRoZSBjYWxsaW5nIGU6ZnVuY3Rpb24+IHRhZy4KICoKICogICBAcmV0dXJuIHN0cmluZyBUaGUgcmVzdWx0aW5nIEhUTUwgY29kZSwgd2hpY2ggdGhlIDxlOmZ1bmN0aW9uPiB0YWcgd2l0aGluIHRoZSB0ZW1wbGF0ZSBpcyByZXBsYWNlZCB3aXRoLgogKi8KcHVibGljIGZ1bmN0aW9uIHByb2Nlc3Moc3RyaW5nICRodG1sLCBhcnJheSAkZGF0YSA9IGFycmF5KCkpOnN0cmluZwp7CgoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRodG1sOwoKfQoKfQoK', 
        'lib' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlfjsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcbGliY1x7ZGIsIGRlYnVnfTsKCi8qKgogKiBCbGFuayBsaWJyYXJ5IGZpbGUgd2hlcmUgeW91IGNhbiBhZGQgCiAqIGFueSBhbmQgYWxsIG1ldGhvZHMgLyBwcm9wZXJ0aWVzIHlvdSBkZXNpcmUuCiAqLwpjbGFzcyB+YWxpYXN+CnsKCgp9Cgo=', 
        'modal' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxtb2RhbDsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcbGliY1x7ZGIsIGRlYnVnfTsKCi8qKgogKiBDbGFzcyB0aGF0IGhhbmRsZXMgdGhlIG1vZGFsIC0tIHRoZSBjZW50ZXIgCiAqIHBvcC11cCBwYW5lbC4KICovCmNsYXNzIH5hbGlhc34KewoKLyoqCiAqKiBTaG93IHRoZSBtb2RhbCBib3guICBVc2VkIHRvIGdhdGhlciBhbnkgCiAqIG5lY2Vzc2FyeSBkYXRhYmFzZSBpbmZvcm1hdGlvbiwgYW5kIGFzc2lnbiB0ZW1wbGF0ZSB2YXJpYWJsZXMsIGV0Yy4KICovCgpwdWJsaWMgZnVuY3Rpb24gc2hvdygpCnsKCgp9Cgp9Cgo=', 
        'service' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxzZXJ2aWNlOwoKLyoqCiAqIFBhcmVudCBhYnN0cmFjdCBjbGFzcyBmb3IgdGhlIHNlcnZpY2UsIHVzZWQgdG8gaGVscCAKICogY29udHJvbCB0aGUgZmxvdyBvZiBkYXRhIHRvIC8gZnJvbSBhZGFwdGVycywgYW5kIGRlZmluZSB0aGUgYWJzdHJhY3QgbWV0aG9kcyByZXF1aXJlZCAKICogYnkgYWRhcHRlcnMuCiAqLwpjbGFzcyB+YWxpYXN+CnsKCiAgICAvKioKICAgICAqIE9wdGlvbmFsbHkgZGVmaW5lIGEgQkFTRTY0IGVuY29kZWQgc3RyaW5nIGhlcmUsIGFuZCB0aGlzIHdpbGwgYmUgdXNlZCBhcyB0aGUgZGVmYXVsdCBjb2RlIAogICAgICogZm9yIGFsbCBhZGFwdGVycyBjcmVhdGVkIGZvciB0aGlzIHNlcnZpY2UuCiAgICAgKiAKICAgICAqIFVzZSB0aGUgbWVyZ2UgZmllbGQgfmFsaWFzfiAKICAgICAqIGZvciB0aGUgY2xhc3MgbmFtZSwgYW5kIGl0IHdpbGwgYmUgcmVwbGFjZWQgYXBwcm9wcmlhdGVseS4KICAgICAqLwogICAgcHVibGljICRkZWZhdWx0X2NvZGUgPSAnJzsKCgoKfQoK', 
        'tabcontrol' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflx0YWJjb250cm9sOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZGVidWd9OwoKLyoqCiAqIENsYXNzIHRoYXQgaGFuZGxlcyB0aGUgdGFiIGNvbnRyb2wsIGFuZCBpcyBleGVjdXRlZCAKICogZXZlcnkgdGltZSB0aGUgdGFiIGNvbnRyb2wgaXMgZGlzcGxheWVkLgogKi8KY2xhc3MgfmFsaWFzfgp7CgogICAgLy8gRGVmaW5lIHRhYiBwYWdlcwogICAgcHVibGljICR0YWJwYWdlcyA9IGFycmF5KAogICAgICAgICdnZW5lcmFsJyA9PiAnR2VuZXJhbCBTZXR0aW5nc2UnIAogICAgKTsKCi8qKgogKiBQcm9jZXNzIHRoZSB0YWIgY29udHJvbC4KICoKICogSXMgZXhlY3V0ZWQgZXZlcnkgdGltZSB0aGUgdGFiIGNvbnRyb2wgaXMgZGlzcGxheWVkLCAKICogaXMgdXNlZCB0byBwZXJmb3JtIGFueSBhY3Rpb25zIHN1Ym1pdHRlZCB3aXRoaW4gZm9ybXMgCiAqIG9mIHRoZSB0YWIgY29udHJvbCwgYW5kIG1haW5seSB0byByZXRyaWV2ZSBhbmQgYXNzaWduIHZhcmlhYmxlcyAKICogdG8gdGhlIHRlbXBsYXRlIGVuZ2luZS4KICoKICogICAgIEBwYXJhbSBhcnJheSAkZGF0YSBUaGUgYXR0cmlidXRlcyBjb250YWluZWQgd2l0aGluIHRoZSA8ZTpmdW5jdGlvbj4gdGFnIHRoYXQgY2FsbGVkIHRoZSB0YWIgY29udHJvbC4KICovCnB1YmxpYyBmdW5jdGlvbiBwcm9jZXNzKGFycmF5ICRkYXRhKSAKewoKCn0KCn0KCg==', 
        'tabpage' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflx0YWJjb250cm9sXH5wYXJlbnR+OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZGVidWd9OwoKLyoqCiAqIEhhbmRsZXMgdGhlIHNwZWNpZmljcyBvZiB0aGUgb25lIHRhYiBwYWdlLCBhbmQgaXMgCiAqIGV4ZWN1dGVkIGV2ZXJ5IHRpbWUgdGhlIHRhYiBwYWdlIGlzIGRpc3BsYXllZC4KICovCmNsYXNzIH5hbGlhc34gCnsKCiAgICAvLyBQYWdlIHZhcmlhYmxlcwogICAgcHVibGljICRwb3NpdGlvbiA9ICdib3R0b20nOwogICAgcHVibGljICRuYW1lID0gJ35hbGlhc191Y34nOwoKLyoqCiAqIFByb2Nlc3MgdGhlIHRhYiBwYWdlLgogKgogKiBFeGVjdXRlcyBldmVyeSB0aW1lIHRoZSB0YWIgY29udHJvbCBpcyBkaXNwbGF5ZWQsIGFuZCB1c2VkIAogKiB0byBleGVjdXRlIGFueSBuZWNlc3NhcnkgYWN0aW9ucyBmcm9tIGZvcm1zIGZpbGxlZCBvdXQgCiAqIG9uIHRoZSB0YWIgcGFnZSwgYW5kIG1pYW5seSB0byB0cmVpZXZlIHZhcmlhYmxlcyBhbmQgYXNzaWduIAogKiB0aGVtIHRvIHRoZSB0ZW1wbGF0ZS4KICoKICogICAgIEBwYXJhbSBhcnJheSAkZGF0YSBUaGUgYXR0cmlidXRlcyBjb250YWluZCB3aXRoaW4gdGhlIDxlOmZ1bmN0aW9uPiB0YWcgdGhhdCBjYWxsZWQgdGhlIHRhYiBjb250cm9sCiAqLwpwdWJsaWMgZnVuY3Rpb24gcHJvY2VzcyhhcnJheSAkZGF0YSA9IGFycmF5KCkpIAp7CgoKfQoKfQoK', 
        'table' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflx0YWJsZTsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcbGliY1x7ZGIsIGRlYnVnfTsKCgovKioKICogSGFuZGxlcyB0aGUgdGFibGUgaW5jbHVkaW5nIG9idGFpbmluZyB0aGUgcm93cyB0byAKICogZGlzcGxheSwgdG90YWwgcm93cyBpbiB0aGUgdGFibGUsIGZvcm1hdHRpbmcgb2YgY2VsbHMsIGV0Yy4KICovCmNsYXNzIH5hbGlhc34KewoKICAgIC8vIENvbHVtbnMKICAgIHB1YmxpYyAkY29sdW1ucyA9IGFycmF5KAogICAgICAgICdpZCcgPT4gJ0lEJwogICAgKTsKCiAgICAvLyBTb3J0YWJsZSBjb2x1bW5zCiAgICBwdWJsaWMgJHNvcnRhYmxlID0gYXJyYXkoJ2lkJyk7CgogICAgLy8gT3RoZXIgdmFyaWFibGVzCiAgICBwdWJsaWMgJHJvd3NfcGVyX3BhZ2UgPSAyNTsKICAgIHB1YmxpYyAkaGFzX3NlYXJjaCA9IGZhbHNlOwoKICAgIC8vIEZvcm0gZmllbGQgKGxlZnQtbW9zdCBjb2x1bW4pCiAgICBwdWJsaWMgJGZvcm1fZmllbGQgPSAnY2hlY2tib3gnOwogICAgcHVibGljICRmb3JtX25hbWUgPSAnfmFsaWFzfl9pZCc7CiAgICBwdWJsaWMgJGZvcm1fdmFsdWUgPSAnaWQnOyAKCiAgICAvLyBEZWxldGUgYnV0dG9uCiAgICBwdWJsaWMgJGRlbGV0ZV9idXR0b24gPSAnRGVsZXRlIENoZWNrZWQgfmFsaWFzX3VjfnMnOwogICAgcHVibGljICRkZWxldGVfZGJ0YWJsZSA9ICcnOwogICAgcHVibGljICRkZWxldGVfZGJjb2x1bW4gPSAnJzsKCi8qKgogKiBQYXJzZSBhdHRyaWJ1dGVzIHdpdGhpbiA8YTpmdW5jdGlvbj4gdGFnLgogKgogKiBQYXNzZXMgdGhlIGF0dHJpYnV0ZXMgY29udGFpbmVkIHdpdGhpbiB0aGUgPGU6ZnVuY3Rpb24+IHRhZyB0aGF0IGNhbGxlZCB0aGUgdGFibGUuCiAqIFVzZWQgbWFpbmx5IHRvIHNob3cvaGlkZSBjb2x1bW5zLCBhbmQgcmV0cmlldmUgc3Vic2V0cyBvZiAKICogZGF0YSAoZWcuIHNwZWNpZmljIHJlY29yZHMgZm9yIGEgdXNlciBJRCMpLgogKiAKICggICAgIEBwYXJhbSBhcnJheSAkZGF0YSBUaGUgYXR0cmlidXRlcyBjb250YWluZWQgd2l0aGluIHRoZSA8ZTpmdW5jdGlvbj4gdGFnIHRoYXQgY2FsbGVkIHRoZSB0YWJsZS4KICovCnB1YmxpYyBmdW5jdGlvbiBnZXRfYXR0cmlidXRlcyhhcnJheSAkZGF0YSA9IGFycmF5KCkpCnsKCn0KCi8qKgogKiBHZXQgdG90YWwgcm93cy4KICoKICogR2V0IHRoZSB0b3RhbCBudW1iZXIgb2Ygcm93cyBhdmFpbGFibGUgZm9yIHRoaXMgdGFibGUuCiAqIFRoaXMgaXMgdXNlZCB0byBkZXRlcm1pbmUgcGFnaW5hdGlvbiBsaW5rcy4KICogCiAqICAgICBAcGFyYW0gc3RyaW5nICRzZWFyY2hfdGVybSBPbmx5IGFwcGxpY2FibGUgaWYgdGhlIEFKQVggc2VhcmNoIGJveCBoYXMgYmVlbiBzdWJtaXR0ZWQsIGFuZCBpcyB0aGUgdGVybSBiZWluZyBzZWFyY2hlZCBmb3IuCiAqICAgICBAcmV0dXJuIGludCBUaGUgdG90YWwgbnVtYmVyIG9mIHJvd3MgYXZhaWxhYmxlIGZvciB0aGlzIHRhYmxlLgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF90b3RhbChzdHJpbmcgJHNlYXJjaF90ZXJtID0gJycpOmludCAKewoKICAgIC8vIEdldCB0b3RhbAogICAgaWYgKCRzZWFyY2hfdGVybSAhPSAnJykgeyAKICAgICAgICAkdG90YWwgPSBEQjo6Z2V0X2ZpZWxkKCJTRUxFQ1QgY291bnQoKikgRlJPTSB+cGFja2FnZX5ffmFsaWFzfiBXSEVSRSBzb21lX2NvbHVtbiBMSUtFICVscyIsICRzZWFyY2hfdGVybSk7CiAgICB9IGVsc2UgeyAKICAgICAgICAkdG90YWwgPSBEQjo6Z2V0X2ZpZWxkKCJTRUxFQ1QgY291bnQoKikgRlJPTSB+cGFja2FnZX5ffmFsaWFzfiIpOwogICAgfQogICAgaWYgKCR0b3RhbCA9PSAnJykgeyAkdG90YWwgPSAwOyB9CgogICAgLy8gUmV0dXJuCiAgICByZXR1cm4gKGludCkgJHRvdGFsOwoKfQoKLyoqCiAqIEdldCByb3dzIHRvIGRpc3BsYXkKICoKICogR2V0cyB0aGUgYWN0dWFsIHJvd3MgdG8gZGlzcGxheSB0byB0aGUgd2ViIGJyb3dzZXIuCiAqIFVzZWQgZm9yIHdoZW4gaW5pdGlhbGx5IGRpc3BsYXlpbmcgdGhlIHRhYmxlLCBwbHVzIEFKQVggYmFzZWQgc2VhcmNoLCAKICogc29ydCwgYW5kIHBhZ2luYXRpb24uCiAqCiAqICAgICBAcGFyYW0gaW50ICRzdGFydCBUaGUgbnVtYmVyIHRvIHN0YXJ0IHJldHJpZXZpbmcgcm93cyBhdCwgdXNlZCB3aXRoaW4gdGhlIExJTUlUIGNsYXVzZSBvZiB0aGUgU1FMIHN0YXRlbWVudC4KICogICAgIEBwYXJhbSBzdHJpbmcgJHNlYXJjaF90ZXJtIE9ubHkgYXBwbGljYWJsZSBpZiB0aGUgQUpBWCBiYXNlZCBzZWFyY2ggYmFzZSBpcyBzdWJtaXR0ZWQsIGFuZCBpcyB0aGUgdGVybSBiZWluZyBzZWFyY2hlZCBmb3JtLgogKiAgICAgQHBhcmFtIHN0cmluZyAkb3JkZXJfYnkgTXVzdCBoYXZlIGEgZGVmYXVsdCB2YWx1ZSwgYnV0IGNoYW5nZXMgd2hlbiB0aGUgc29ydCBhcnJvd3MgaW4gY29sdW1uIGhlYWRlcnMgYXJlIGNsaWNrZWQuICBVc2VkIHdpdGhpbiB0aGUgT1JERVIgQlkgY2xhdXNlIGluIHRoZSBTUUwgc3RhdGVtZW50LgogKgogKiAgICAgQHJldHVybiBhcnJheSBBbiBhcnJheSBvZiBhc3NvY2lhdGl2ZSBhcnJheXMgZ2l2aW5nIGtleS12YWx1ZSBwYWlycyBvZiB0aGUgcm93cyB0byBkaXNwbGF5LgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9yb3dzKGludCAkc3RhcnQgPSAwLCBzdHJpbmcgJHNlYXJjaF90ZXJtID0gJycsIHN0cmluZyAkb3JkZXJfYnkgPSAnaWQgYXNjJyk6YXJyYXkgCnsKCiAgICAvLyBHZXQgcm93cwogICAgaWYgKCRzZWFyY2hfdGVybSAhPSAnJykgeyAKICAgICAgICAkcm93cyA9IERCOjpxdWVyeSgiU0VMRUNUICogRlJPTSB+cGFja2FnZX5ffmFsaWFzfiBXSEVSRSBzb21lX2NvbHVtbiBMSUtFICVscyBPUkRFUiBCWSAkb3JkZXJfYnkgTElNSVQgJHN0YXJ0LCR0aGlzLT5yb3dzX3Blcl9wYWdlIiwgJHNlYXJjaF90ZXJtKTsKICAgIH0gZWxzZSB7IAogICAgICAgICRyb3dzID0gREI6OnF1ZXJ5KCJTRUxFQ1QgKiBGUk9NIH5wYWNrYWdlfl9+YWxpYXN+IE9SREVSIEJZICRvcmRlcl9ieSBMSU1JVCAkc3RhcnQsJHRoaXMtPnJvd3NfcGVyX3BhZ2UiKTsKICAgIH0KCiAgICAvLyBHbyB0aHJvdWdoIHJvd3MKICAgICRyZXN1bHRzID0gYXJyYXkoKTsKICAgIGZvcmVhY2ggKCRyb3dzIGFzICRyb3cpIHsgCiAgICAgICAgYXJyYXlfcHVzaCgkcmVzdWx0cywgJHRoaXMtPmZvcm1hdF9yb3coJHJvdykpOwogICAgfQoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRyZXN1bHRzOwoKfQoKLyoqCiAqIEZvcm1hdCBhIHNpbmdsZSByb3cuCiAqCiAqIFJldHJpZXZlcyByYXcgZGF0YSBmcm9tIHRoZSBkYXRhYmFzZSwgd2hpY2ggbXVzdCBiZSAKICogZm9ybWF0dGVkIGludG8gdXNlciByZWFkYWJsZSBmb3JtYXQgKGVnLiBmb3JtYXQgYW1vdW50cywgZGF0ZXMsIGV0Yy4pLgogKgogKiAgICAgQHBhcmFtIGFycmF5ICRyb3cgVGhlIHJvdyBmcm9tIHRoZSBkYXRhYmFzZS4KICoKICogICAgIEByZXR1cm4gYXJyYXkgVGhlIHJlc3VsdGluZyBhcnJheSB0aGF0IHNob3VsZCBiZSBkaXNwbGF5ZWQgdG8gdGhlIGJyb3dzZXIuCiAqLwpwdWJsaWMgZnVuY3Rpb24gZm9ybWF0X3JvdyhhcnJheSAkcm93KTphcnJheSAKewoKICAgIC8vIEZvcm1hdCByb3cKCgogICAgLy8gUmV0dXJuCiAgICByZXR1cm4gJHJvdzsKCn0KCn0KCg==', 
        'view' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XHZpZXdzOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXHtkYiwgZGVidWcsIHZpZXd9OwoKLyoqCiAqIEFsbCBjb2RlIGJlbG93IHRoaXMgbGluZSBpcyBhdXRvbWF0aWNhbGx5IGV4ZWN1dGVkIHdoZW4gdGhpcyB0ZW1wbGF0ZSBpcyB2aWV3ZWQsIAogKiBhbmQgdXNlZCB0byBwZXJmb3JtIGFueSBuZWNlc3NhcnkgdGVtcGxhdGUgc3BlY2lmaWMgYWN0aW9ucy4KICovCgoKCg==', 
        'test' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSB0ZXN0c1x+cGFja2FnZX47Cgp1c2UgYXBleFxhcHA7CnVzZSBhcGV4XGxpYmNce2RiLCBkZWJ1Z307CnVzZSBhcGV4XGFwcFx0ZXN0c1x0ZXN0OwoKCi8qKgogKiBBZGQgYW55IG5lY2Vzc2FyeSBwaHBVbml0IHRlc3QgbWV0aG9kcyBpbnRvIHRoaXMgY2xhc3MuICBZb3UgbWF5IGV4ZWN1dGUgYWxsIAogKiB0ZXN0cyBieSBydW5uaW5nOiAgcGhwIGFwZXgucGhwIHRlc3QgfnBhY2thZ2V+CiAqLwpjbGFzcyB0ZXN0X35hbGlhc34gZXh0ZW5kcyB0ZXN0CnsKCi8qKgogKiBzZXRVcAogKi8KcHVibGljIGZ1bmN0aW9uIHNldFVwKCk6dm9pZAp7CgogICAgLy8gR2V0IGFwcAogICAgaWYgKCEkYXBwID0gYXBwOjpnZXRfaW5zdGFuY2UoKSkgeyAKICAgICAgICAkYXBwID0gbmV3IGFwcCgndGVzdCcpOwogICAgfQoKfQoKLyoqCiAqIHRlYXJEb3duCiAqLwpwdWJsaWMgZnVuY3Rpb24gdGVhckRvd24oKTp2b2lkCnsKCn0KCgoKfQoK', 
        'worker' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflx3b3JrZXI7Cgp1c2UgYXBleFxhcHA7CnVzZSBhcGV4XGxpYmNce2RiLCBkZWJ1Z307CnVzZSBhcGV4XGFwcFxpbnRlcmZhY2VzXG1zZ1xFdmVudE1lc3NhZ2VJbnRlcmZhY2UgYXMgZXZlbnQ7CgovKioKICogQ2xhc3MgdGhhdCBoYW5kbGVzIGEgd29ya2VyIC8gbGlzdGVuZXIgY29tcG9uZW50LCB3aGljaCBpcyAKICogdXNlZCBmb3Igb25lLXdheSBkaXJlY3QgYW5kIHR3by13YXkgUlBDIG1lc3NhZ2VzIHZpYSBSYWJiaXRNUSwgCiAqIGFuZCBzaG91bGQgYmUgdXRpbGl6ZWQgYSBnb29kIGRlYWwuCiAqLwpjbGFzcyB+YWxpYXN+CnsKCiAgICAvLyBSb3V0aW5nIGtleQogICAgcHVibGljICRyb3V0aW5nX2tleSA9ICd+dmFsdWV+JzsKCgoKfQoK'
    ];

    // Set destination dirs
    public static $dest_dirs = array(
        'child' => 'src', 
        'docs' => 'docs/~alias~', 
        'etc' => 'etc/~alias~',
        'ext' => '',  
        'src' => 'src/~alias~', 
        'tests' => 'tests/~alias~', 
        'views' => 'views'
    );


/**
 * Create a new component.  Used via the apex.php script, to create a new 
 * component including necessary files. 
 *
 * @param string $type The type of component (template, worker, lib, etc.)
 * @param string $comp_alias Alias of the component in Apex format (ie. PACKAGE:[PARENT:]ALIAS
 * @param string $owner Optional owner, only required for a few components (adapter, tab_page, worker)
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
    //if ($php_file == '') { 
    //    throw new ComponentException('no_php_file', $type, '', $alias, $package, $parent);
    //}
    $php_file = SITE_PATH . '/' . $php_file;

    // Check if PHP file exists already
    if (file_exists($php_file)) { 
        throw new ComponentException('php_file_exists', $type, '', $alias, $package, $parent);
    }

    // Get default PHP code
    if ($type == 'adapter' && $service = components::load('service', $parent, $package)) { 
        $code = $service->default_code ?? '';
        if ($code == '') { $code = self::$code_templates['adapter']; }
        $code = base64_decode($code);
    } else { 
        $code = base64_decode(self::$code_templates[$type]);
    }

    // Replace merge fields in default code as needed
    $code = str_replace("~package~", $package, $code);
    $code = str_replace("~parent~", $parent, $code);
    $code = str_replace("~alias~", $alias, $code);
    $code = str_replace("~alias_uc~", ucwords($alias), $code);
    $code = str_replace("~value~", $value, $code);

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
    file_put_contents($php_file, base64_decode(self::$code_templates['view']));

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
 * @param string $owner Only needed for adapter and tabpage components, and is the owner package of the component.
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

    // Add worker, if needed
    if ($type == 'worker') { 
        $redis_key = 'config:worker:' . $value;
        redis::sadd($redis_key, $package . ':' . $alias);
    }

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
 * @param string $owner Only needed for adapter and tabpage components, and is the owner package of the component.
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
    if ($parent == '' && in_array($type, array_keys(COMPONENT_PARENT_TYPES))) { 
        throw new ComponentException('no_parent', $type, $comp_alias);
    } elseif ($parent != '' && !components::check(COMPONENT_PARENT_TYPES[$type], $package . ':' . $parent)) { 
        throw new ComponentException('parent_not_exists', $type, $comp_alias);
    }

    // Check owner
    if ($owner == '' && ($type == 'adapter' || $type == 'tabpage')) { 
        throw new ComponentException('no_owner', $type, $comp_alias);
    } elseif ($owner == '' && $value == '' && $type == 'worker') { 
        throw new ComponentException('no_worker_routing_key');
    }

    // Set owner as needed
    if ($type == 'worker' && $value == '') { 
        $value = $owner;
        $owner = '';
    }
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

    // Check if crontab job already is scheduled
    $cron_alias = $package . ':' . $alias;
    if ($row = db::get_row("SELECT * FROM internal_tasks WHERE adapter = 'crontab' AND alias = %s", $cron_alias)) { 
        return true;
    }

    // Load file
    $cron = components::load('cron', $alias, $package);
    $autorun = $cron->autorun ?? 1;
    $time_interval = $cron->time_interval ?? '';

    // Schedule crontab job, if needed
    if ($autorun == 1 && preg_match("/^\w\d+$/", $time_interval)) { 
        $execute_time = date::add_interval($time_interval);

        $tasks_client = components::load('service', 'tasks', 'core');
        $tasks_client->add('crontab', $cron_alias, $execute_time);
    }

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
    if ($type == 'tabcontrol' || $type == 'service') {
        $child_type = array_flip(COMPONENT_PARENT_TYPES)[$type];

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
        db::query("DELETE FROM internal_tasks WHERE adapter = 'crontab' AND alias = %s", $package . ':' . $alias); 
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

/**
 * Sync from temp directory
 *
 * @param string $pkg_alias The package alias to snyc.
 * @param string $tmp_dir The temp directory which holds the unpacked git archive.
 * @param string $rollback_dir Optional rollback directory, if upgrading.
 *
 * @return array List of all newly added files.
 */
public static function sync_from_dir(string $pkg_alias, string $tmp_dir, string $rollback_dir = '')
{

    // Go through dest dirs
    $new_files = array();
    foreach (self::$dest_dirs as $source_dir => $dest_dir) { 
        if (!is_dir("$tmp_dir/$source_dir")) { continue; }
        $dest_dir = str_replace("~alias~", $pkg_alias, $dest_dir);

        // Go through all files
        $files = io::parse_dir("$tmp_dir/$source_dir");
        foreach ($files as $file) { 

            // Check for new file
            $dest_file = SITE_PATH . '/' . $dest_dir . '/' . $file; 
            if (!file_exists($dest_file)) { 
                $new_files[] = $source_dir . '/' . $file;
            }

            // Get hashes
            $chk_hash = sha1_file("$tmp_dir/$source_dir/$file");
            $hash = file_exists($dest_file) ? sha1_file($dest_file) : '';
            if ($hash == $chk_hash) { continue; }

            // Copy to rollback dir, if needed
            if (file_exists($dest_file) && $rollback_dir != '') { 
                io::create_dir(dirname("$rollback_dir/$source_dir/$file"));
                rename($dest_file, "$rollback_dir/$source_dir/$file");
            } elseif (file_exists($dest_file)) { 
                @unlink($dest_file);
            }

            // Debug
            debug::add(5, tr("Copying file from git repo to local, {1}", $file));

            // Copy file
            io::create_dir(dirname($dest_file));
            copy("$tmp_dir/$source_dir/$file", $dest_file);

            // Add component
            self::add_from_filename($pkg_alias, "$source_dir/$file");
        }
    }

    // Copy over /data/ directory, if needed
    if ($rollback_dir == '' && is_dir("$tmp_dir/data")) {

        $import_data = []; 
        $files = io::parse_dir("$tmp_dir/data");
        foreach ($files as $file) { 
            list($data_package, $file) = explode("/", $file, 2);
            if (!isset($import_data[$data_package])) { $import_data[$data_package] = []; }
            $import_data[$data_package][$file] = "$tmp_dir/data/$data_package/$file";
        }

        // Import all data
        foreach ($import_data as $package => $files) { 
            $client = app::make(package_config::class, ['pkg_alias' => $package]);
            $pkg = $client->load();

            if (method_exists($pkg, 'import_data')) { 
                $pkg->import_data($package, $files);
            }
        }
    }

    // Return
    return $new_files;

}

/**
 * Add from filename
 *
 * @param string $pkg_alias The alias of the package.
 * @param string $file The filename to add component for.
 */
public static function add_from_filename(string $pkg_alias, string $file)
{

    // Parse filename
    if (!list($type, $comp_alias) = self::parse_filename($pkg_alias, $file)) { 
        return;
    }

    // Get routing key, if worker
    if ($type == 'worker' && preg_match("/^src\/worker\/(.+?)\.php$/", $file, $match)) { 
        $worker = components::load('worker', $match[1], $pkg_alias);
        $value = $worker->routing_key ?? '';
    } else { 
        $value = '';
    }

    // Add component
    self::add($type, $comp_alias, $value, 0, $pkg_alias);

}

/**
 * Remove a component based on filename.
 *
 * @param string $pkg_alias The alias of the package.
 * @param string $file The filename.
 */
public static function remove_from_filename(string $pkg_alias, string $filename)
{

    // Get type / alias
    if (!list($type, $comp_alias) = self::parse_filename($pkg_alias, $filename)) { 
        return false;
    }

    // Remove
self::remove($type, $comp_alias);

}

/**
 * Parse filename, and get type, package, alias and parent.
 *
 * @param string $pkg_alias The alias of the package.
 * @param string $file The filename to parse.
 *
 * @return mixed Type type, comp_alias and value if successful, and false otherwise.
 */
protected static function parse_filename(string $pkg_alias, string $file)
{

    // Initialize
    $parts = explode("/", preg_replace("/\.(php|tpl)$/", "", $file));
    $subdir = array_shift($parts);

    // Initial checks
    if (!in_array($subdir, array('child', 'src', 'tests','views'))) { return false; }
    elseif ($subdir == 'src' && $parts[0] == 'tpl') { return false; }
    elseif ($subdir == 'views' && array_shift($parts) != 'tpl') { return false; }

    // Check for view
    if ($subdir == 'views') { 
        $type = 'view';
        $comp_alias = implode('/', $parts);

    // Check for /tests/
    } elseif ($subdir == 'tests') { 
        $type = 'test';
        $comp_alias = $pkg_alias . ':' . preg_replace("/\_test$/", "", implode('/', $parts));

    // Component
    } else { 
        $package = $subdir == 'child' ? array_shift($parts) : $pkg_alias;
        if (count($parts) > 1 && !isset(COMPONENT_TYPES[$parts[0]])) { 
            $type = 'lib';
            $alias = implode('/', $parts);
            $parent = '';
        } else { 
            $type = count($parts) == 1 ? 'lib' : array_shift($parts);
            $parent = count($parts) > 1 ? array_shift($parts) : '';
            $alias = $parts[0];
        }
        // Get comp alias
        if ($parent != '') { 
            $flipped = array_flip(COMPONENT_PARENT_TYPES);
            if (isset($flipped[$type])) { $type = $flipped[$type]; }

            $comp_alias = implode(':', array($package, $parent, $alias));
        } else { 
            $comp_alias = $package . ':' . $alias;
        }
    }

    // Return
    return array($type, $comp_alias);

}



/**
 * Get component vars
 *
 * Used while compiling a package / upgrade, and only used to shorten 
 * the code, and ensure all component var arrays within the toc.json 
 * files are standardized.
 *
 * @param string $type The component type.
 * @param string $alias The component alias.
 * @param string $package Alias of the package.
 * @param string $parent Alias of the parent, if exists.
 * @param string $value Optional value of the component.
 * @param int $order_num Optional order num of the component.
 *
 * @return array The vars array to include in components.json file.
 */
public static function get_vars(string $type, string $alias, string $package, string $parent = '', string $value = '', int $order_num = 0)
{

    // Set vars array
    $vars = array(
        'type' => $type, 
        'order_num' => $order_num, 
        'package' => $package, 
        'parent' => $parent, 
        'alias' => $alias, 
        'value' => $value
    );

    // Return
    return $vars;

}




}

