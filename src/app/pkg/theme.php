<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\app\sys\network;
use apex\libc\io;
use apex\app\exceptions\RepoException;
use apex\app\exceptions\ThemeException;

/**
 * Handles all theme functions including create, publish, download, install 
 * and remove. 
 */
class theme
{


    // Default files for new themes
    private $default_files = [
        'sections/header.tpl' => 'PCFET0NUWVBFIGh0bWw+CjxodG1sIGxhbmc9ImVuIj4KPGhlYWQ+CiAgICA8dGl0bGU+PGE6cGFnZV90aXRsZSB0ZXh0b25seT0iMSI+PC90aXRsZT4KCiAgICA8IS0tIEphdmFzY3JpcHQgLyBDU1MgZmlsZXMgaGVyZSAtLT4KCjwvaGVhZD4KCjxib2R5PgoKICAgIDxkaXYgY2xhc3M9IndyYXBwZXIiPgoKICAgICAgICA8aDE+PGE6cGFnZV90aXRsZSB0ZXh0b25seT0iMSI+PC9oMT4KCiAgICAgICAgPGE6Y2FsbG91dHM+CgoKCgo=', 
        'sections/footer.tpl' => 'CiAgICA8L2Rpdj4KCjwvYm9keT4KPC9odG1sPgoKCg==', 
        'layouts/default.tpl' => 'CjxhOnRoZW1lIHNlY3Rpb249ImhlYWRlci50cGwiPgoKPGE6cGFnZV9jb250ZW50cz4KCjxhOnRoZW1lIHNlY3Rpb249ImZvb3Rlci50cGwiPgoKCg==', 
        'layouts/homepage.tpl' => 'CjxhOnRoZW1lIHNlY3Rpb249ImhlYWRlci50cGwiPgoKPGE6cGFnZV9jb250ZW50cz4KCjxhOnRoZW1lIHNlY3Rpb249ImZvb3Rlci50cGwiPgoKCg==', 
        'theme.php' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCi8qKgogKiBUaGVtZSBjb25maWd1cmF0aW9uLiAgRGVmaW5lcyB2YXJpYWJsZSBiYXNpYyAKICogcHJvcGVydGllcyByZWdhcmRpbmcgdGhlIHRoZW1lLgoqLwpjbGFzcyB0aGVtZV9+YWxpYXN+IAp7CgogICAgLy8gUHJvcGVydGllcwogICAgcHVibGljICRhcmVhID0gJ35hcmVhfic7CiAgICBwdWJsaWMgJGFjY2VzcyA9ICdwdWJsaWMnOwogICAgcHVibGljICRuYW1lID0gJ35hbGlhc34nOwogICAgcHVibGljICRkZXNjcmlwdGlvbiA9ICcnOwoKICAgIC8vIEF1dGhvciBkZXRhaWxzCiAgICBwdWJsaWMgJGF1dGhvcl9uYW1lID0gJyc7CiAgICBwdWJsaWMgJGF1dGhvcl9lbWFpbCA9ICcnOwogICAgcHVibGljICRhdXRob3JfdXJsID0gJyc7CgogICAgLyoqCiAgICAgKiBFbnZhdG8gaXRlbSBJRC4gIGlmIHRoaXMgaXMgZGVmaW5lZCwgdXNlcnMgd2lsbCBuZWVkIHRvIHB1cmNoYXNlIHRoZSB0aGVtZSBmcm9tIFRoZW1lRm9yZXN0IGZpcnN0LCAKICAgICAqIGFuZCBlbnRlciB0aGVpciBsaWNlbnNlIGtleSBiZWZvcmUgZG93bmxvYWRpbmcgdGhlIHRoZW1lIHRvIEFwZXguICBUaGUgbGljZW5zZSBrZXkgCiAgICAgKiB3aWxsIGJlIHZlcmlmaWVkIHZpYSBFbnZhdG8ncyBBUEksIHRvIGVuc3VyZSB0aGUgdXNlciBwdXJjaGFzZWQgdGhlIHRoZW1lLgogICAgICogCiAgICAgKiBZb3UgbXVzdCBhbHNvIHNwZWNpZnkgeW91ciBFbnZhdG8gdXNlcm5hbWUsIGFuZCB0aGUgZnVsbCAKICAgICAqIFVSTCB0byB0aGUgdGhlbWUgb24gdGhlIFRoZW1lRm9yZXN0IG1hcmtldHBsYWNlLiAgUGxlYXNlIGFsc28gCiAgICAgKiBlbnN1cmUgeW91IGFscmVhZHkgaGF2ZSBhIGRlc2lnbmVyIGFjY291bnQgd2l0aCB1cywgYXMgd2UgZG8gbmVlZCB0byAKICAgICAqIHN0b3JlIHlvdXIgRW52YXRvIEFQSSBrZXkgaW4gb3VyIHN5c3RlbSBpbiBvcmRlciB0byB2ZXJpZnkgcHVyY2hhc2VzLiAgQ29udGFjdCB1cyB0byBvcGVuIGEgZnJlZSBhY2NvdW50LgogICAgICovCiAgICBwdWJsaWMgJGVudmF0b19pdGVtX2lkID0gJyc7CiAgICBwdWJsaWMgJGVudmF0b191c2VybmFtZSA9ICcnOwogICAgcHVibGljICRlbnZhdG9fdXJsID0gJyc7Cgp9Cgo=', 
        'tags.tpl' => 'CioqKioqKioqKioqKioqKioqKioqKioqKioqKioqKgoqIFRoaXMgZmlsZSBjb250YWlucyBhbGwgSFRNTCBzbmlwcGV0cyBmb3IgdGhlIHNwZWNpYWwgCiogSFRNTCB0YWdzIHRoYXQgYXJlIHVzZWQgdGhyb3VnaG91dCBBcGV4LiAgVGhlc2UgYXJlIHRhZ3MgcHJlZml4ZWQgd2l0aCAiYToiLCBzdWNoIGFzIAoqIDxhOmJveD4sIDxhOmZvcm1fdGFibGU+LCBhbmQgb3RoZXJzLgoqCiogQmVsb3cgYXJlIGxpbmVzIHdpdGggdGhlIGZvcm1hdCAiW1t0YWdfbmFtZV1dIiwgYW5kIGV2ZXJ5dGhpbmcgYmVsb3cgdGhhdCAKKiBsaW5lIHJlcHJlc2VudHMgdGhlIGNvbnRlbnRzIG9mIHRoYXQgSFRNTCB0YWcsIHVudGlsIHRoZSBuZXh0IG9jY3VycmVuY2Ugb2YgIltbdGFnX25hbWVdXSIgaXMgcmVhY2hlZC4KKgoqIFRhZyBuYW1lcyB0aGF0IGNvbnRhaW4gYSBwZXJpb2QgKCIuIikgc2lnbmlmeSBhIGNoaWxkIGl0ZW0sIGFzIHlvdSB3aWxsIG5vdGljZSBiZWxvdy4KKgoqKioqKioqKioqKioqKioqKioqKioqKioqKioqKioKCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpmb3JtX3RhYmxlPiAuLi4gPC9hOmZvcm1fdGFibGU+CiogPGE6Rk9STV9GSUVMRD4KKgoqIFRoZSBmb3JtIHRhYmxlLCBhbmQgdmFyaW91cyBmb3JtIGZpZWxkIGVsZW1lbnRzCioqKioqKioqKioqKioqKioqKioqCgpbW2Zvcm1fdGFibGVdXQo8dGFibGUgYm9yZGVyPSIwIiBjbGFzcz0iZm9ybV90YWJsZSIgc3R5bGU9IndpZHRoOiB+d2lkdGh+OyBhbGlnbjogfmFsaWdufjsiPgogICAgfmNvbnRlbnRzfgo8L3RhYmxlPgoKCltbZm9ybV90YWJsZS5yb3ddXQo8dHI+CiAgICA8dGQ+PGxhYmVsIGZvcj0ifm5hbWV+Ij5+bGFiZWx+OjwvbGFiZWw+PC90ZD4KICAgIDx0ZD48ZGl2IGNsYXNzPSJmb3JtLWdyb3VwIj4KICAgICAgICB+Zm9ybV9maWVsZH4KICAgIDwvZGl2PjwvdGQ+CjwvdHI+CgoKW1tmb3JtX3RhYmxlLnNlcGFyYXRvcl1dCjx0cj4KICAgIDx0ZCBjb2xzcGFuPSIyIj48aDU+fmxhYmVsfjwvaDU+PC90ZD4KPC90cj4KCgpbW2Zvcm0uc3VibWl0XV0KPGRpdiBjbGFzcz0idGV4dC1sZWZ0Ij4KICAgIDxidXR0b24gdHlwZT0ic3VibWl0IiBuYW1lPSJzdWJtaXQiIHZhbHVlPSJ+dmFsdWV+IiBjbGFzcz0iYnRuIGJ0bi1wcmltYXJ5IGJ0bi1+c2l6ZX4iPn5sYWJlbH48L2J1dHRvbj4KPC9kaXY+CgpbW2Zvcm0ucmVzZXRdXQo8IS0tIDxidXR0b24gdHlwZT0icmVzZXQiIGNsYXNzPSJidG4gYnRuLXByaW1hcnkgYnRuLW1kIj5SZXNldCBGb3JtPC9idXR0b24+IC0tPgoKCltbZm9ybS5idXR0b25dXQo8YSBocmVmPSJ+aHJlZn4iIGNsYXNzPSJidG4gYnRuLXByaW5hcnkgYnRuLX5zaXplfiI+fmxhYmVsfjwvYT4KCgpbW2Zvcm0uYm9vbGVhbl1dCjxkaXYgY2xhc3M9InJhZGlvZm9ybSI+CiAgICA8aW5wdXQgdHlwZT0icmFkaW8iIG5hbWU9In5uYW1lfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgdmFsdWU9IjEiIH5jaGtfeWVzfiAvPiA8c3Bhbj5ZZXM8L3NwYW4+IAogICAgPGlucHV0IHR5cGU9InJhZGlvIiBuYW1lPSJ+bmFtZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHZhbHVlPSIwIiB+Y2hrX25vfiAvPiA8c3Bhbj5Obzwvc3Bhbj4gCjwvZGl2PgoKW1tmb3JtLnNlbGVjdF1dCjxzZWxlY3QgbmFtZT0ifm5hbWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiB+d2lkdGh+IH5vbmNoYW5nZX4+CiAgICB+b3B0aW9uc34KPC9zZWxlY3Q+CgoKW1tmb3JtLnRleHRib3hdXQo8aW5wdXQgdHlwZT0ifnR5cGV+IiBuYW1lPSJ+bmFtZX4iIHZhbHVlPSJ+dmFsdWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiBpZD0ifmlkfiIgfnBsYWNlaG9sZGVyfiB+YWN0aW9uc34gfnZhbGlkYXRpb25+IC8+CgoKCltbZm9ybS50ZXh0YXJlYV1dCjx0ZXh0YXJlYSBuYW1lPSJ+bmFtZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIGlkPSJ+aWR+IiBzdHlsZT0id2lkdGg6IDEwMCUiIH5wbGFjZWhvbGRlcn4+fnZhbHVlfjwvdGV4dGFyZWE+CgoKW1tmb3JtLmVkaXRvcl1dCjx0ZXh0YXJlYSBuYW1lPSJ+bmFtZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIGlkPSJ+bmFtZX4iIHN0eWxlPSJ3aWR0aDogMTAwJSIgfnBsYWNlaG9sZGVyfj5+dmFsdWV+PC90ZXh0YXJlYT4KPHNjcmlwdCBzcmM9Ii9wbHVnaW5zL2NrZWRpdG9yL2NrZWRpdG9yLmpzIiB0eXBlPSJ0ZXh0L2phdmFzY3JpcHQiPjwvc2NyaXB0Pgo8c2NyaXB0PkNLRURJVE9SLnJlcGxhY2UoJ35uYW1lficpOzwvc2NyaXB0PgoKCltbZm9ybS5waG9uZV1dCjxkaXYgY2xhc3M9ImZvcm0tZ3JvdXAiPgogICAgPHNlbGVjdCBuYW1lPSJ+bmFtZX5fY291bnRyeSIgY2xhc3M9ImZvcm0tY29udHJvbCBjb2wtbGctMiI+CiAgICAgICAgfmNvdW50cnlfY29kZV9vcHRpb25zfgogICAgPC9zZWxlY3Q+IAogICAgPGlucHV0IHR5cGU9InRleHQiIG5hbWU9In5uYW1lfiIgdmFsdWU9In52YWx1ZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wgY29sLWxnLTEwIiAgfnBsYWNlaG9sZGVyfj4KPC9kaXY+CgpbW2Zvcm0uYW1vdW50XV0KPHNwYW4gc3R5bGU9ImZsb2F0OiBsZWZ0OyI+fmN1cnJlbmN5X3NpZ25+PC9zcGFuPiAKPGlucHV0IHR5cGU9InRleHQiIG5hbWU9In5uYW1lfiIgdmFsdWU9In52YWx1ZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogNjBweDsgZmxvYXQ6IGxlZnQ7IiB+cGxhY2Vob2xkZXJ+IGRhdGEtcGFyc2xleS10eXBlPSJkZWNpbWFsIj4KCgpbW2Zvcm0uZGF0ZV1dCjxzZWxlY3QgbmFtZT0ifm5hbWV+X21vbnRoIiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDEyMHB4OyBmbG9hdDogbGVmdDsiPgogICAgfm1vbnRoX29wdGlvbnN+Cjwvc2VsZWN0PiAKPHNlbGVjdCBuYW1lPSJ+bmFtZX5fZGF5fiIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiAzMHB4OyBmbG9hdDogbGVmdDsiPgogICAgfmRheV9vcHRpb25zfgo8L3NlbGVjdD4sIAo8c2VsZWN0IG5hbWU9In5uYW1lfl95ZWFyfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA3MHB4OyBmbG9hdDogbGVmdDsiPgogICAgfnllYXJfb3B0aW9uc34KPC9zZWxlY3Q+CgpbW2Zvcm0udGltZV1dCjxzZWxlY3QgbmFtZT0ifm5hbWV+X2hvdXIiIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogNjBweDsgZmxvYXQ6IGxlZnQ7Ij4KICAgIH5ob3VyX29wdGlvbnN+Cjwvc2VsZWN0PiA6IAo8c2VsZWN0IG5hbWU9In5uYW1lfl9taW4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogNjBweDsgZmxvYXQ6IGxlZnQ7Ij4KICAgIH5taW51dGVfb3B0aW9uc34KPC9zZWxlY3Q+CgoKW1tmb3JtLmRhdGVfaW50ZXJ2YWxdXQo8ZGl2IGNsYXNzPSJmb3JtLWdyb3VwIj4KICAgIDxkaXYgY2xhc3M9ImNvbC1sZy04IiBzdHlsZT0icGFkZGluZy1sZWZ0OiAwIj4KICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ifm5hbWV+X251bSIgY2xhc3M9ImZvcm0tY29udHJvbCIgdmFsdWU9In5udW1+IiA+IAogICAgPC9kaXY+CiAgICA8ZGl2IGNsYXNzPSJjb2wtbGctNCIgc3R5bGU9InBhZGRpbmctcmlnaHQ6IDAiPgogICAgICAgIDxzZWxlY3QgbmFtZT0ifm5hbWV+X3BlcmlvZCIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiAxMDAlIiA+CiAgICAgICAgICAgIH5wZXJpb2Rfb3B0aW9uc34KICAgICAgICA8L3NlbGVjdD4KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6Ym94PiAuLi4gPC9hOmJveD4KKiA8YTpib3hfaGVhZGVyIHRpdGxlPSIuLi4iPiAuLi4gPC9hOmJveF9oZWFkZXI+CioKKiBDb250YWluZXJzIC8gcGFuZWxzIHRoYXQgaGVscCBzZXBhcmF0ZSBkaWZmZXJlbnQgc2VjdGlvbnMgb2YgdGhlIHBhZ2UuICBDYW4gb3B0aW9uYWxseSAKKiBjb250YWluIGEgaGVhZGVyIHdpdGggdGl0bGUuCioqKioqKioqKioqKioqKioqKioqCgpbW2JveF1dCjxkaXYgY2xhc3M9InBhbmVsIHBhbmVsLWRlZmF1bHQiPgogICAgPGRpdiBjbGFzcz0icGFuZWwtaGVhZGluZyI+IH5ib3hfaGVhZGVyfjwvZGl2PgogICAgPGRpdiBjbGFzcz0icGFuZWwtYm9keSI+CiAgICAgICAgfmNvbnRlbnRzfgogICAgPC9kaXY+CjwvZGl2PgoKW1tib3guaGVhZGVyXV0KPHNwYW4gc3R5bGU9ImJvcmRlci1ib3R0b206IDFweCBzb2xpZCAjMzMzMzMzOyBtYXJnaW4tYm90dG9tOiA4cHg7Ij4KICAgIDxoMz5+dGl0bGV+PC9oMz4KICAgIH5jb250ZW50c34KPC9zcGFuPgoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6aW5wdXRfYm94PiAuLi4gPC9hOmlucHV0X2JveD4KKgoqIE1lYW50IGZvciBhIGZ1bGwgd2lkdGggc2l6ZWgsIHNob3J0IHNpemVkIGJhci4gIFVzZWQgCiogZm9yIHRoaW5ncyBzdWNoIGFzIGEgc2VhcmNoIHRleHRib3gsIG9yIG90aGVyIGJhcnMgdG8gc2VwYXJhdGUgdGhlbSBmcm9tIAoqIHRoZSByZXN0IG9mIHRoZSBwYWdlIGNvbnRlbnQuCioKKiBFeGFtcGxlIG9mIHRoaXMgaXMgVXNlcnMtPk1hbmFnZSBVc2VyIG1lbnUgb2YgdGhlIGFkbWluaXN0cmF0aW9uIAoqIHBhbmVsLCB3aGVyZSB0aGUgc2VhcmNoIGJveCBpcyBzdXJyb3VuZGVkIGJ5IGFuIGlucHV0IGJveC4KKioqKioqKioqKioqKioqKioqKioKCltbaW5wdXRfYm94XV0KPGRpdiBjbGFzcz0icGFuZWwgcGFuZWwtZGVmYXVsdCBzZWFyY2hfdXNlciI+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1ib2R5Ij4KICAgICAgICB+Y29udGVudHN+CiAgICA8L2Rpdj4KPC9kaXY+CgoKCgoqKioqKioqKioqKioqKioqKioqKgoqIDxhOmNhbGxvdXRzPgoqCiogVGhlIGNhbGxvdXRzIC8gaW5mb3JtYXRpb25hbCBtZXNzYWdlcyB0aGF0IGFyZSBkaXNwbGF5ZWQgb24gdGhlIAoqIHRvcCBvZiBwYWdlcyBhZnRlciBhbiBhY3Rpb24gaXMgcGVyZm9ybWVkLiAgVGhlc2UgbWVzc2FnZXMgYXJlIAoqIGZvcjogc3VjY2VzcywgZXJyb3IsIHdhcm5pbmcsIGluZm8uIAoqCiogVGhlIGZpcnN0IGVsZW1lbnQgaXMgdGhlIEhUTUwgY29kZSBvZiB0aGUgY2FsbG91dHMgaXRzZWxmLCB0aGUgc2Vjb25kIAoqIGFuZCB0aGlyZCBlbGVtZW50cyBhcmUgSlNPTiBlbmNvZGVkIHN0cmluZ3MgdGhhdCBkZWZpbmUgdGhlIAoqIENTUyBhbmQgaWNvbiBhbGlhc2VzIHRvIHVzZSBmb3IgZWFjaCBtZXNzYWdlIHR5cGUuCioqKioqKioqKioqKioqKioqKioqCgpbW2NhbGxvdXRzXV0KPGRpdiBjbGFzcz0iYWxlcnQgYWxlcnQtfmNzc19hbGlhc34iPjxwPgogICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgZGF0YS1kaXNtaXNzPSJhbGVydCI+JnRpbWVzOzwvYnV0dG9uPgogICAgfm1lc3NhZ2Vzfgo8L3A+PC9kaXY+CgoKW1tjYWxsb3V0cy5jc3NdXQpbCiAgICAic3VjY2VzcyI6ICJzdWNjZXNzIiwgCiAgICAiZXJyb3IiOiAiZXJyb3IiLCAKICAgICJ3YXJuaW5nIjogIndhcm5pbmciLCAKICAgICJpbmZvIjogImluZm8iCl0KCgpbW2NhbGxvdXRzLmljb25dXQpbCiAgICAic3VjY2VzcyI6ICJmYSBmYS1jaGVjayIsIAogICAgImVycm9yIjogImZhIGZhLWJhbiIsIAogICAgIndhcm5pbmciOiAiZmEgZmEtd2FybmluZyIsIAogICAgImluZm8iOiAiZmEgZmEtaW5mbyIKXQoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6bmF2X21lbnU+CioKKiBUaGUgbmF2aWdhdGlvbiBtZW51IG9mIHRoZSB0aGVtZSwgaW5jbHVkaW5nIGhlYWRlciAvIHNlcGFyYXRvciAKKiBpdGVtcywgcGFyZW50IG1lbnVzLCBhbmQgc3VibWVudXMuCioqKioqKioqKioqKioqKioqKioqCgpbW25hdi5oZWFkZXJdXQo8bGk+PGgzPn5uYW1lfjwvbGk+CgoKW1tuYXYucGFyZW50XV0KPGxpPgogICAgPGEgaHJlZj0ifnVybH4iPn5pY29ufiB+bmFtZX48L2E+CiAgICA8dWw+CiAgICAgICAgfnN1Ym1lbnVzfgogICAgPC91bD4KPC9saT4KCgpbW25hdi5tZW51XV0KPGxpPjxhIGhyZWY9In51cmx+Ij5+aWNvbn5+bmFtZX48L2E+PC9saT4KCgoqKioqKioqKioqKioqKioqKioqKgoqIDxhOnRhYl9jb250cm9sPiAuLi4gPC9hOnRhYl9jb250cm9sPgoqIDxhOnRhYl9wYWdlIG5hbWU9Ii4uLiI+IC4uLiA8L2E6dGFiX3BhZ2U+CioKKiBUaGUgdGFiIGNvbnRyb2xzLiAgSW5jbHVkZXMgdGhlIHRhYiBjb250cm9sIGl0c2VsZiwgbmF2IGl0ZW1zIGFuZCAKKiB0aGUgYm9keSBwYW5lIG9mIHRhYiBwYWdlcy4KKioqKioqKioqKioqKioqKioqKioKCltbdGFiX2NvbnRyb2xdXQoKPGRpdiBjbGFzcz0ibmF2LXRhYnMtY3VzdG9tIj4KICAgIDx1bCBjbGFzcz0ibmF2IG5hdi10YWJzIj4KICAgICAgICB+bmF2X2l0ZW1zfgogICAgPC91bD4KCiAgICA8ZGl2IGNsYXNzPSJ0YWItY29udGVudCI+CiAgICAgICAgfnRhYl9wYWdlc34KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKW1t0YWJfY29udHJvbC5uYXZfaXRlbV1dCjxsaSBjbGFzcz0ifmFjdGl2ZX4iPjxhIGhyZWY9IiN0YWJ+dGFiX251bX4iIGRhdGEtdG9nZ2xlPSJ0YWIiPn5uYW1lfjwvYT48L2xpPgoKCltbdGFiX2NvbnRyb2wucGFnZV1dCjxkaXYgY2xhc3M9InRhYi1wYW5lIH5hY3RpdmV+IiBpZD0idGFifnRhYl9udW1+Ij4KICAgIH5jb250ZW50c34KPC9kaXY+CgoKW1t0YWJfY29udHJvbC5jc3NfYWN0aXZlXV0KYWN0aXZlCgoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6ZGF0YV90YWJsZT4gLi4uIDwvYTpkYXRhX3RhYmxlPgoqIDxhOnRhYmxlX3NlYXJjaF9iYXI+CiogPGE6dGg+IC4uLiA8YTp0aD4KKiA8YTp0cj4gLi4uIDwvYTp0cj4KKgoqIFRoZSBkYXRhIHRhYmxlcyB1c2VkIHRocm91Z2hvdXQgdGhlIHNvZnR3YXJlLgoqKioqKioqKioqKioqKioqKioqKgoKW1tkYXRhX3RhYmxlXV0KPHRhYmxlIGNsYXNzPSJ0YWJsZSB0YWJsZS1ib3JkZXJlZCB0YWJsZS1zdHJpcGVkIHRhYmxlLWhvdmVyIiBpZD0ifnRhYmxlX2lkfiI+CiAgICA8dGhlYWQ+CiAgICAgICAgfnNlYXJjaF9iYXJ+CiAgICA8dHI+CiAgICAgICAgfmhlYWRlcl9jZWxsc34KICAgIDwvdHI+CiAgICA8L3RoZWFkPgoKICAgIDx0Ym9keSBpZD0ifnRhYmxlX2lkfl90Ym9keSIgY2xhc3M9ImJvZHl0YWJsZSI+CiAgICAgICAgfnRhYmxlX2JvZHl+CiAgICA8L3Rib2R5PgoKICAgIDx0Zm9vdD48dHI+CiAgICAgICAgPHRkIGNvbHNwYW49In50b3RhbF9jb2x1bW5zfiIgYWxpZ249InJpZ2h0Ij4KICAgICAgICAgICAgfmRlbGV0ZV9idXR0b25+CiAgICAgICAgICAgIH5wYWdpbmF0aW9ufgogICAgICAgIDwvdGQ+CiAgICA8L3RyPjwvdGZvb3Q+CjwvdGFibGU+CgoKW1tkYXRhX3RhYmxlLnRoXV0KPHRoIGNsYXNzPSJib3hoZWFkZXIiPiA8c3Bhbj5+bmFtZX48L3NwYW4+IH5zb3J0X2Rlc2N+IH5zb3J0X2FzY348L3RoPgoKCltbZGF0YV90YWJsZS5zb3J0X2FzY11dCjxhIGhyZWY9ImphdmFzY3JpcHQ6YWpheF9zZW5kKCdjb3JlL3NvcnRfdGFibGUnLCAnfmFqYXhfZGF0YX4mc29ydF9jb2w9fmNvbF9hbGlhc34mc29ydF9kaXI9YXNjJywgJ25vbmUnKTsiIGJvcmRlcj0iMCIgdGl0bGU9IlNvcnQgQXNjZW5kaW5nIH5jb2xfYWxpYXN+IiBjbGFzcz0iYXNjIj4KICAgIDxpIGNsYXNzPSJmYSBmYS1zb3J0LWFzYyI+PC9pPgo8L2E+CgoKW1tkYXRhX3RhYmxlLnNvcnRfZGVzY11dCjxhIGhyZWY9ImphdmFzY3JpcHQ6YWpheF9zZW5kKCdjb3JlL3NvcnRfdGFibGUnLCAnfmFqYXhfZGF0YX4mc29ydF9jb2w9fmNvbF9hbGlhc34mc29ydF9kaXI9ZGVzYycsICdub25lJyk7IiBib3JkZXI9IjAiIHRpdGxlPSJTb3J0IERlY2VuZGluZyB+Y29sX2FsaWFzfiIgY2xhc3M9ImRlc2MiPgogICAgPGkgY2xhc3M9ImZhIGZhLXNvcnQtZGVzYyI+PC9pPgo8L2E+CgoKW1tkYXRhX3RhYmxlLnNlYXJjaF9iYXJdXQo8dHI+CiAgICA8dGQgc3R5bGU9ImJvcmRlci10b3A6MXB4IHNvbGlkICNjY2MiIGNvbHNwYW49In50b3RhbF9jb2x1bW5zfiIgYWxpZ249InJpZ2h0Ij4KICAgICAgICA8ZGl2IGNsYXNzPSJmb3Jtc2VhcmNoIj4KICAgICAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIG5hbWU9InNlYXJjaF9+dGFibGVfaWR+IiBwbGFjZWhvbGRlcj0ifnNlYXJjaF9sYWJlbH4uLi4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogMjEwcHg7Ij4gCiAgICAgICAgICAgIDxhIGhyZWY9ImphdmFzY3JpcHQ6YWpheF9zZW5kKCdjb3JlL3NlYXJjaF90YWJsZScsICd+YWpheF9kYXRhficsICdzZWFyY2hffnRhYmxlX2lkficpOyIgY2xhc3M9ImJ0biBidG4tcHJpbWFyeSBidG4tbWQiPjxpIGNsYXNzPSJmYSBmYS1zZWFyY2giPjwvaT48L2E+CiAgICAgICAgPC9kaXY+CiAgICA8L3RkPgo8L3RyPgoKCltbZGF0YV90YWJsZS5kZWxldGVfYnV0dG9uXV0KPGEgaHJlZj0iamF2YXNjcmlwdDphamF4X2NvbmZpcm0oJ0FyZSB5b3Ugc3VyZSB5b3Ugd2FudCB0byBkZWxldGUgdGhlIGNoZWNrZWQgcmVjb3Jkcz8nLCAnY29yZS9kZWxldGVfcm93cycsICd+YWpheF9kYXRhficsICcnKTsiIGNsYXNzPSJidG4gYnRuLXByaW1hcnkgYnRuLW1kIGJvdG9udGVzdCIgc3R5bGU9ImZsb2F0OiBsZWZ0OyI+fmRlbGV0ZV9idXR0b25fbGFiZWx+PC9hPgoKCgoqKioqKioqKioqKioqKioqKioqKgoqIDxhOnBhZ2luYXRpb24+CioKKiBQYWdpbmF0aW9uIGxpbmtzLCBnZW5lcmFsbHkgZGlzcGxheWVkIGF0IHRoZSBib3R0b20gb2YgCiogZGF0YSB0YWJsZXMsIGJ1dCBjYW4gYmUgdXNlZCBhbnl3aGVyZS4KKioqKioqKioqKioqKioqKioqKioKCltbcGFnaW5hdGlvbl1dCjxzcGFuIGlkPSJwZ25zdW1tYXJ5X35pZH4iIHN0eWxlPSJ2ZXJ0aWNhbC1hbGlnbjogbWlkZGxlOyBmb250LXNpemU6IDhwdDsgbWFyZ2luLXJpZ2h0OiA3cHg7Ij4KICAgIDxiPn5zdGFydF9yZWNvcmR+IC0gfmVuZF9yZWNvcmR+PC9iPiBvZiA8Yj5+dG90YWxfcmVjb3Jkc348L2I+Cjwvc3Bhbj4KCjx1bCBjbGFzcz0icGFnaW5hdGlvbiIgaWQgPSJwZ25ffmlkfiI+CiAgICB+aXRlbXN+CjwvdWw+CgoKW1twYWdlaW5hdGlvbi5pdGVtXV0KPGxpIHN0eWxlPSJkaXNwbGF5OiB+ZGlzcGxheX47Ij48YSBocmVmPSJ+dXJsfiI+fm5hbWV+PC9hPjwvbGk+CgpbW3BhZ2luYXRpb24uYWN0aXZlX2l0ZW1dXQo8bGkgY2xhc3M9ImFjdGl2ZSI+PGE+fnBhZ2V+PC9hPjwvbGk+CgoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6ZHJvcGRvd25fYWxlcnRzPgoqIDxhOmRyb3Bkb3duX21lc3NhZ2VzPgoqCiogVGhlIGxpc3QgaXRlbXMgdXNlZCBmb3IgdGhlIHR3byBkcm9wIGRvd24gbGlzdHMsIG5vdGlmaWNhdGlvbnMgLyBhbGVydHMgYW5kIAoqIG1lc3NhZ2VzLiAgVGhlc2UgYXJlIGdlbmVyYWxseSBkaXNwbGF5ZWQgaW4gdGhlIHRvcCByaWdodCBjb3JuZXIgCiogb2YgYWRtaW4gcGFuZWwgLyBtZW1iZXIgYXJlYSB0aGVtZXMuCioqKioqKioqKioqKioqKioqKioqCgoKW1tkcm9wZG93bi5hbGVydF1dCjxsaSBjbGFzcz0ibWVkaWEiPgogICAgPGRpdiBjbGFzcz0ibWVkaWEtYm9keSI+CiAgICAgICAgPGEgaHJlZj0ifnVybH4iPn5tZXNzYWdlfgogICAgICAgIDxkaXYgY2xhc3M9InRleHQtbXV0ZWQgZm9udC1zaXplLXNtIj5+dGltZX48L2Rpdj4KCTwvYT4KICAgIDwvZGl2Pgo8L2xpPgoKCltbZHJvcGRvd24ubWVzc2FnZV1dCjxsaSBjbGFzcz0ibWVkaWEiPgogICAgPGRpdiBjbGFzcz0ibWVkaWEtYm9keSI+CgogICAgICAgIDxkaXYgY2xhc3M9Im1lZGlhLXRpdGxlIj4KICAgICAgICAgICAgPGEgaHJlZj0ifnVybH4iPgogICAgICAgICAgICAgICAgPHNwYW4gY2xhc3M9ImZvbnQtd2VpZ2h0LXNlbWlib2xkIj5+ZnJvbX48L3NwYW4+CiAgICAgICAgICAgICAgICA8c3BhbiBjbGFzcz0idGV4dC1tdXRlZCBmbG9hdC1yaWdodCBmb250LXNpemUtc20iPn50aW1lfjwvc3Bhbj4KICAgICAgICAgICAgPC9hPgogICAgICAgIDwvZGl2PgoKICAgICAgICA8c3BhbiBjbGFzcz0idGV4dC1tdXRlZCI+fm1lc3NhZ2V+PC9zcGFuPgogICAgPC9kaXY+CjwvbGk+CgoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6Ym94bGlzdHM+CioKKiBUaGUgYm94bGlzdHMgYXMgc2VlbiBvbiBwYWdlcyBzdWNoIGFzIFNldHRpbmdzLT5Vc2Vycy4gIFVzZWQgdG8gCiogZGlzcGxheSBsaW5rcyB0byBtdWx0aXBsZSBwYWdlcyB3aXRoIGRlc2NyaXB0aW9ucy4KKioqKioqKioqKioqKioqKioqKioKCltbYm94bGlzdF1dCjx1bCBjbGFzcz0iYm94bGlzdCI+CiAgICB+aXRlbXN+CjwvdWw+CgoKCltbYm94bGlzdC5pdGVtXV0KPGxpPgogICAgPGEgaHJlZj0ifnVybH4iPgogICAgICAgIDxiPn50aXRsZX48L2I+PGJyIC8+CiAgICAgICAgfmRlc2NyaXB0aW9ufgogICAgPC9hPgo8L2xpPgoKCioqKioqKioqKioqKioqKioqKioqCiogZGFzaGJvYXJkCioKKiBIVE1MIHNuaXBwZXRzIGZvciB0aGUgZGFzaGJvYXJkIHdpZGdldHMuCioqKioqKioqKioqKioqKioqKioqCgpbW2Rhc2hib2FyZF1dCgo8ZGl2IGNsYXNzPSJyb3cgYm94Z3JhZiI+CiAgICB+dG9wX2l0ZW1zfgo8L2Rpdj4KCjxkaXYgY2xhc3M9InBhbmVsIHBhbmVsLWZsYXQiPgogICAgPGE6ZnVuY3Rpb24gYWxpYXM9ImRpc3BsYXlfdGFiY29udHJvbCIgdGFiY29udHJvbD0iY29yZTpkYXNoYm9hcmQiPgo8L2Rpdj4KCjxkaXYgY2xhc3M9InNpZGViYXIgc2lkZWJhci1saWdodCBiZy10cmFuc3BhcmVudCBzaWRlYmFyLWNvbXBvbmVudCBzaWRlYmFyLWNvbXBvbmVudC1yaWdodCBib3JkZXItMCBzaGFkb3ctMCBvcmRlci0xIG9yZGVyLW1kLTIgc2lkZWJhci1leHBhbmQtbWQiPgogICAgPGRpdiBjbGFzcz0ic2lkZWJhci1jb250ZW50Ij4KICAgICAgICB+cmlnaHRfaXRlbXN+CiAgICA8L2Rpdj4KPC9kaXY+CgpbW2Rhc2hib2FyZC50b3BfaXRlbV1dCjxkaXYgY2xhc3M9ImNvbC1sZy00Ij4KICAgIDxkaXYgY2xhc3M9In5wYW5lbF9jbGFzc34iPgogICAgICAgIDxkaXYgY2xhc3M9InBhbmVsLWJvZHkiPgoKICAgICAgICAgICAgPGgzIGNsYXNzPSJuby1tYXJnaW4iPn5jb250ZW50c348L2gzPgogICAgICAgICAgICAgICAgfnRpdGxlfgogICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0idGV4dC1tdXRlZCB0ZXh0LXNpemUtc21hbGwiPn5jb250ZW50c348L2Rpdj4KICAgICAgICA8L2Rpdj4KICAgICAgICA8ZGl2IGNsYXNzPSJjb250YWluZXItZmx1aWQiPgogICAgICAgICAgICA8ZGl2IGlkPSJ+ZGl2aWR+Ij48L2Rpdj4KICAgICAgICA8L2Rpdj4KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKW1tkYXNoYm9hcmQucmlnaHRfaXRlbV1dCjxkaXYgY2xhc3M9ImNhcmQiPgogICAgPGRpdiBjbGFzcz0iY2FyZC1oZWFkZXIgYmctdHJhbnNwYXJlbnQgaGVhZGVyLWVsZW1lbnRzLWlubGluZSI+CiAgICAgICAgPHNwYW4gY2xhc3M9ImNhcmQtdGl0bGUgZm9udC13ZWlnaHQtc2VtaWJvbGQiPn50aXRsZX48L3NwYW4+CiAgICA8L2Rpdj4KICAgIDxkaXYgY2xhc3M9ImNhcmQtYm9keSI+CiAgICAgICAgPHVsIGNsYXNzPSJtZWRpYS1saXN0Ij4KICAgICAgICAgICAgPGxpIGNsYXNzPSJtZWRpYSI+CiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJtZWRpYS1ib2R5Ij4KICAgICAgICAgICAgICAgICAgICB+Y29udGVudHN+CiAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPC9saT4KICAgICAgICA8L3VsPgogICAgPC9kaXY+CjwvZGl2PgoKCgoK'
    ];


/**
 * Create a new theme 
 *
 * @param string $theme_alias The alias of the new theme to create
 * @param int $repo_id The ID# of the repo to publish the theme to
 * @param string $area The area the theme is for ('members' or 'public'), defaults to 'public'
 *
 * @return int The ID# of the newly created theme
 */
public function create(string $theme_alias, int $repo_id, string $area = 'public')
{ 

    // Debug
    debug::add(2, tr("Starting create theme with alias, {1}", $theme_alias));

    // Initial check
    if ($theme_alias == '') { 
        throw new ThemeException('invalid_alias');
    } elseif (preg_match("/\s\W]/", $theme_alias)) { 
        throw new ThemeException('invalid_alias', $theme_alias);
    }
    $theme_alias = strtolower($theme_alias);

    // Check if theme exists
    if ($row = db::get_row("SELECT * FROM internal_themes WHERE alias = %s", $theme_alias)) { 
        throw new ThemeException('theme_exists', $theme_alias);
    }

    // Create directories
    $theme_dir = SITE_PATH . '/views/themes/' . $theme_alias;
    io::create_dir($theme_dir);
    io::create_dir("$theme_dir/sections");
    io::create_dir("$theme_dir/layouts");
    io::create_dir(SITE_PATH . '/public/themes/' . $theme_alias);

    // Save default files
    foreach ($this->default_files as $filename => $code) { 

        // Get code
        $code = base64_decode($code);
        $code = str_replace("~alias~", $theme_alias, $code);
        $code = str_replace("~area~", $area, $code);
        file_put_contents("$theme_dir/$filename", $code);
    }
    file_put_contents("$theme_dir/Readme.md", '');

    // Add to database
    db::insert('internal_themes', array(
        'is_owner' => 1,
        'repo_id' => $repo_id,
        'area' => $area,
        'alias' => $theme_alias,
        'name' => $theme_alias)
    );
    $theme_id = db::insert_id();

    // Debug
    debug::add(1, tr("Successfully created new theme with alias {1}", $theme_alias));

    // Return
    return $theme_id;

}

/**
 * Publish a theme to a repository 
 *
 * @param string $theme_alias The alias of the theme to publish
 */
public function publish(string $theme_alias)
{ 

    // Debug
    debug::add(3, tr("Starting to publish theme with alias, {1}", $theme_alias));

    // Get theme
    if (!$row = db::get_row("SELECT * FROM internal_themes WHERE alias = %s", $theme_alias)) { 
        throw new ThemeException('not_exists', $theme_alias);
    }

    // Load theme file
    $class_name = 'theme_' . $theme_alias;
    require_once(SITE_PATH . '/views/themes/' . $theme_alias . '/theme.php');
    $theme = new $class_name();

    // Debug
    debug::add(4, tr("Publishing theme, successfully loaded theme configuration for alias, {1}", $theme_alias));

    // Set variables
    $access = $theme->access ?? $row['access'];
    $area = $theme->area ?? $row['area'];
    $price = $theme->price ?? 0;
    $envato_username = $theme->envato_username ?? '';
    $envato_item_id = $theme->envato_item_id ?? '';
    $envato_url = $theme->envato_url ?? '';
    $name = $theme->name ?? $row['name'];

    // Update database
    db::update('internal_themes', array(
        'area' => $area,
        'name' => $name),
    "alias = %s", $theme_alias);

    // Get repo
    if (!$repo_id = db::get_idrow('internal_repos', $row['repo_id'])) { 
        throw new RepoException('not_exists', $repo_id);
    }

    // Compile theme
    $zip_file = $this->compile($theme_alias);

    // Set request
    $request = array(
        'type' => 'theme',
        'version' => '1.0.0',
        'access' => $access,
        'area' => $area,
        'price' => $price, 
        'envato_username' => $envato_username, 
        'envato_item_id' => $envato_item_id, 
        'envato_url' => $envato_url, 
        'name' => $name,
        'description' => ($theme->description ?? ''),
        'author_name' => ($theme->author_name ?? ''),
        'author_email' => ($theme->author_email ?? ''),
        'author_url' => ($theme->author_url ?? ''),
        'envato_item_id' => ($theme->envato_item_id ?? ''),
        'envato_username' => ($theme->envato_username ?? ''),
        'envato_url' => ($theme->envato_url ?? '')
    );

    // Get Readme.md file, if exists
    $readme_file = SITE_PATH . '/views/themes/' . $theme_alias . '/Readme.md';
    if (file_exists($readme_file)) { 
        $request['readme'] = base64_encode(file_get_contents($readme_file));
    }

    // Send repo request
    $client = app::make(network::class);
    $vars = $client->send_repo_request((int) $row['repo_id'], $theme_alias, 'publish', $request, false, 'apex_theme_' . $theme_alias . '.zip');

    // Debug
    debug::add(1, tr("Successfully published theme to repository, {1}", $theme_alias));


}

/**
 * Compile a theme into a zip archive 
 *
 * @param string $theme_alias The alias of the theme to archive
 */
protected function compile(string $theme_alias)
{ 

    // Debug
    debug::add(4, tr("Start compile theme with alias, {1}", $theme_alias));

// Create /public/ directory within theme
    $theme_dir = SITE_PATH . '/views/themes/' . $theme_alias;
    if (is_dir("$theme_dir/public")) { io::remove_dir("$theme_dir/public"); }
    io::create_dir("$theme_dir/public");

    // Copy over public directory
    $files = io::parse_dir(SITE_PATH . '/public/themes/' . $theme_alias);
    foreach ($files as $file) { 
        io::create_dir(dirname("$theme_dir/public/$file"));
    copy(SITE_PATH . '/public/themes/' . $theme_alias . '/' . $file, "$theme_dir/public/$file");
    }

    // Archive theme
    $zip_file = sys_get_temp_dir() . '/apex_theme_' . $theme_alias . '.zip';
    if (file_exists($zip_file)) { @unlink($zip_file); }
    io::create_zip_archive($theme_dir, $zip_file);

    // Clean up
    io::remove_dir("$theme_dir/public");

    // Debug
    debug::add(4, tr("Successfully compiled theme, {1}", $theme_alias));

    // Return
    return $zip_file;

}

/**
 * Download and install a theme 
 * 
 * @param string $theme_alias The alias of the theme to install. 
 * @param int $repo_id Optional ID# of the repo to download from.  If unspecified, all repos will be checked.
 * @param string $purchase_code Optional purchase code for any commercial themes to be unlocked by the repository.
 */
public function install(string $theme_alias, int $repo_id = 0, string $purchase_code = '')
{ 

    // Debug
    debug::add(2, tr("Starting to download and install theme, {1}", $theme_alias));

    // Download
    list($repo_id, $zip_file, $vars) = $this->download($theme_alias, $repo_id, $purchase_code);

    // Unpack zip archive
    $theme_dir = SITE_PATH . '/views/themes/' . $theme_alias;
    if (is_dir($theme_dir)) { io::remove_dir($theme_dir); }
    io::unpack_zip_archive($zip_file, $theme_dir);

    // Create /public/ directory
    $public_dir = SITE_PATH . '/public/themes/' . $theme_alias;
        if (is_dir($public_dir)) { io::remove_dir($public_dir); }
    io::create_dir($public_dir);

    // Copy over /public/ directory
    $files = io::parse_dir("$theme_dir/public");
    foreach ($files as $file) { 
        io::create_dir(dirname("$public_dir/$file"));
    copy("$theme_dir/public/$file", "$public_dir/$file");
    }
    io::remove_dir("$theme_dir/public");

    // Copy over .tpl files, if needed
    if (is_dir("$theme_dir/tpl") && ($vars['area'] == 'public' || $vars['area'] == 'members')) { 

        // Get setup to copy
        $tpl_dir = SITE_PATH . '/views/tpl/' . $vars['area'];
        $tpl_files = io::parse_dir("$theme_dir/tpl");

        // Create tpl_bak directory
        io::remove_dir("$theme_dir/tpl_bak");
        io::create_dir("$theme_dir/tpl_bak");

        // Copy over files
        foreach ($tpl_files as $file) {

            // Rename, if exists 
            if (file_exists("$tpl_dir/$file")) {
                io::create_dir(dirname("$theme_dir/tpl_bak/$file"));
                rename("$tpl_dir/$file", "$theme_dir/tpl_bak/$file");
            }
            copy("$theme_dir/tpl/$file", "$tpl_dir/$file");
        }
    }

    // Add to database
    db::insert('internal_themes', array(
        'is_owner' => 0,
        'repo_id' => $repo_id,
        'area' => $vars['area'],
        'alias' => $theme_alias,
        'name' => $vars['name'])
    );

    // Activate theme
    app::change_theme($vars['area'], $theme_alias);

    // Debug
    debug::add(1, tr("Successfully downloaded and installed theme, {1}", $theme_alias));

    // Return
    return true;

}

/**
 * Download a theme from the repository. 
 * 
 * @param string $theme_alias The alias of the theme to download. 
 * @param int $repo_id Optional ID# of the repo to download from.  If unspecified, all repos will be checked.
 * @param string $purchase_code Optional purchase code for commercial themes to unlock the theme from the repository.
 */
protected function download(string $theme_alias, int $repo_id = 0, string $purchase_code = '')
{ 

    // Debug
    debug::add(4, tr("Starting to download theme from repository, {1}", $theme_alias));

    // Initialize network client
    $network = app::make(network::class);

    // Get repo, if needed
    if ($repo_id == 0) { 

        // Check theme on all repos
        $repos = $network->check_package($theme_alias, 'theme');
        if (count($repos) == 0) { 
            throw new ThemeException('not_exists_repo', $theme_alias);
        }
        $repo_id = array_keys($repos)[0];
    }

    // Get repo
    if (!$repo = db::get_idrow('internal_repos', $repo_id)) { 
throw RepoException('not_exists', $repo_id);
    }

    // Set request
    $request = array(
        'type' => 'theme', 
        'purchase_code' => $purchase_code
    );

    // Download theme
    $vars = $network->send_repo_request((int) $repo_id, $theme_alias, 'download', $request);

    // Save zip file
    $zip_file = sys_get_temp_dir() . '/apex_theme_' . $theme_alias . '.zip';
    if (file_exists($zip_file)) { @unlink($zip_file); }
    io::download_file($vars['download_url'], $zip_file);

    // Debug
    debug::add(4, tr("Successfully downloaded theme, {1}", $theme_alias));

    // Return
    return array($repo_id, $zip_file, $vars);

}

/**
 * Remove a theme from the system. 
 *
 * @param string $theme_alias The alias of the theme to remove.
 */
public function remove(string $theme_alias)
{ 

    // Debug
    debug::add(4, tr("Starting removal of theme, {1}", $theme_alias));

    // Ensure theme exists
    if (!$row = db::get_row("SELECT * FROM internal_themes WHERE alias = %s", $theme_alias)) { 
        throw new ThemeException('not_exists', $theme_alias);
    }

    // Remove dirs
    io::remove_dir(SITE_PATH . '/views/themes/' . $theme_alias);
    io::remove_dir(SITE_PATH . '/public/themes/' . $theme_alias);

    // Delete from database
    db::query("DELETE FROM internal_themes WHERE alias = %s", $theme_alias);

    // Debug
    debug::add(1, tr("Successfully deleted theme from system, {1}", $theme_alias));

    // Return
return true;

}

/**
 * Initialize a file
 *
 * Used via the apex\app\sys\apex_cli::init_theme() method, will go 
 * through the specified file, and initialize for integration.  This includes 
 * updating all links to JS / CSS / images with the ~theme_uri~ tag, updating 
 * the page title, and so on.
 *
 * @param string $file Full path to the file to initialize.
 */
public function init_file(string $file)
{

    // Get contents
    $html = file_get_contents($file);

    // Go through all HTML tags
    preg_match_all("/<(script|link|img) (.+?)>/i", $html, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) {

        // Check for attribute
        $attr_name = $match[1] == 'link' ? 'href' : 'src';
        if (!preg_match("/$attr_name=\"(.+?)\"/i", $match[2], $attr_match)) { 
            continue;
        }
        if (preg_match("/^(http|~theme_uri~)/i", $attr_match[1])) { continue; }

        // Update as necessary
        $attr = $attr_name . '="~theme_uri~/' . ltrim($attr_match[1], '/') . '"';
        $html = str_replace($match[0], str_replace($attr_match[0], $attr, $match[0]), $html);
    }

    // Page title
    $html = preg_replace("/<title>(.*?)<\/title>/si", "<title><a:page_title textonly=\"1\"></title>", $html);
    $html = preg_replace("/(?\/)index\.html/", "/index", $html);

    // Save file
    file_put_contents($file, $html);

}


}


