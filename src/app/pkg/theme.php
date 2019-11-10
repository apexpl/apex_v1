<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\sys\network;
use apex\svc\io;
use apex\app\exceptions\RepoException;
use apex\app\exceptions\ThemeException;
use CurlFile;


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
        'theme.php' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCi8qKgogKiBUaGVtZSBjb25maWd1cmF0aW9uLiAgRGVmaW5lcyB2YXJpYWJsZSBiYXNpYyAKICogcHJvcGVydGllcyByZWdhcmRpbmcgdGhlIHRoZW1lLgoqLwpjbGFzcyB0aGVtZV9+YWxpYXN+IAp7CgogICAgLy8gUHJvcGVydGllcwogICAgcHVibGljICRhcmVhID0gJ35hcmVhfic7CiAgICBwdWJsaWMgJGFjY2VzcyA9ICdwdWJsaWMnOwogICAgcHVibGljICRuYW1lID0gJ35hbGlhc34nOwogICAgcHVibGljICRkZXNjcmlwdGlvbiA9ICcnOwoKICAgIC8vIEF1dGhvciBkZXRhaWxzCiAgICBwdWJsaWMgJGF1dGhvcl9uYW1lID0gJyc7CiAgICBwdWJsaWMgJGF1dGhvcl9lbWFpbCA9ICcnOwogICAgcHVibGljICRhdXRob3JfdXJsID0gJyc7CgogICAgLyoqCiAgICAgKiBFbnZhdG8gaXRlbSBJRC4gIGlmIHRoaXMgaXMgZGVmaW5lZCwgdXNlcnMgd2lsbCBuZWVkIHRvIHB1cmNoYXNlIHRoZSB0aGVtZSBmcm9tIFRoZW1lRm9yZXN0IGZpcnN0LCAKICAgICAqIGFuZCBlbnRlciB0aGVpciBsaWNlbnNlIGtleSBiZWZvcmUgZG93bmxvYWRpbmcgdGhlIHRoZW1lIHRvIEFwZXguICBUaGUgbGljZW5zZSBrZXkgCiAgICAgKiB3aWxsIGJlIHZlcmlmaWVkIHZpYSBFbnZhdG8ncyBBUEksIHRvIGVuc3VyZSB0aGUgdXNlciBwdXJjaGFzZWQgdGhlIHRoZW1lLgogICAgICogCiAgICAgKiBZb3UgbXVzdCBhbHNvIHNwZWNpZnkgeW91ciBFbnZhdG8gdXNlcm5hbWUsIGFuZCB0aGUgZnVsbCAKICAgICAqIFVSTCB0byB0aGUgdGhlbWUgb24gdGhlIFRoZW1lRm9yZXN0IG1hcmtldHBsYWNlLiAgUGxlYXNlIGFsc28gCiAgICAgKiBlbnN1cmUgeW91IGFscmVhZHkgaGF2ZSBhIGRlc2lnbmVyIGFjY291bnQgd2l0aCB1cywgYXMgd2UgZG8gbmVlZCB0byAKICAgICAqIHN0b3JlIHlvdXIgRW52YXRvIEFQSSBrZXkgaW4gb3VyIHN5c3RlbSBpbiBvcmRlciB0byB2ZXJpZnkgcHVyY2hhc2VzLiAgQ29udGFjdCB1cyB0byBvcGVuIGEgZnJlZSBhY2NvdW50LgogICAgICovCiAgICBwdWJsaWMgJGVudmF0b19pdGVtX2lkID0gJyc7CiAgICBwdWJsaWMgJGVudmF0b191c2VybmFtZSA9ICcnOwogICAgcHVibGljICRlbnZhdG9fdXJsID0gJyc7Cgp9Cgo=', 
        'tags.tpl' => 'CioqKioqKioqKioqKioqKioqKioqKioqKioqKioqKgoqIFRoaXMgZmlsZSBjb250YWlucyBhbGwgSFRNTCBzbmlwcGV0cyBmb3IgdGhlIHNwZWNpYWwgCiogSFRNTCB0YWdzIHRoYXQgYXJlIHVzZWQgdGhyb3VnaG91dCBBcGV4LiAgVGhlc2UgYXJlIHRhZ3MgcHJlZml4ZWQgd2l0aCAiYToiLCBzdWNoIGFzIAoqIDxhOmJveD4sIDxhOmZvcm1fdGFibGU+LCBhbmQgb3RoZXJzLgoqCiogQmVsb3cgYXJlIGxpbmVzIHdpdGggdGhlIGZvcm1hdCAiW1t0YWdfbmFtZV1dIiwgYW5kIGV2ZXJ5dGhpbmcgYmVsb3cgdGhhdCAKKiBsaW5lIHJlcHJlc2VudHMgdGhlIGNvbnRlbnRzIG9mIHRoYXQgSFRNTCB0YWcsIHVudGlsIHRoZSBuZXh0IG9jY3VycmVuY2Ugb2YgIltbdGFnX25hbWVdXSIgaXMgcmVhY2hlZC4KKgoqIFRhZyBuYW1lcyB0aGF0IGNvbnRhaW4gYSBwZXJpb2QgKCIuIikgc2lnbmlmeSBhIGNoaWxkIGl0ZW0sIGFzIHlvdSB3aWxsIG5vdGljZSBiZWxvdy4KKgoqKioqKioqKioqKioqKioqKioqKioqKioqKioqKioKCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpmb3JtX3RhYmxlPiAuLi4gPC9hOmZvcm1fdGFibGU+CiogPGE6Rk9STV9GSUVMRD4KKgoqIFRoZSBmb3JtIHRhYmxlLCBhbmQgdmFyaW91cyBmb3JtIGZpZWxkIGVsZW1lbnRzCioqKioqKioqKioqKioqKioqKioqCgpbW2Zvcm1fdGFibGVdXQo8dGFibGUgYm9yZGVyPSIwIiBjbGFzcz0iZm9ybV90YWJsZSIgc3R5bGU9IndpZHRoOiB+d2lkdGh+OyBhbGlnbjogfmFsaWdufjsiPgogICAgfmNvbnRlbnRzfgo8L3RhYmxlPgoKCltbZm9ybV90YWJsZS5yb3ddXQo8dHI+CiAgICA8dGQ+PGxhYmVsIGZvcj0ifm5hbWV+Ij5+bGFiZWx+OjwvbGFiZWw+PC90ZD4KICAgIDx0ZD48ZGl2IGNsYXNzPSJmb3JtLWdyb3VwIj4KICAgICAgICB+Zm9ybV9maWVsZH4KICAgIDwvZGl2PjwvdGQ+CjwvdHI+CgoKW1tmb3JtX3RhYmxlLnNlcGFyYXRvcl1dCjx0cj4KICAgIDx0ZCBjb2xzcGFuPSIyIj48aDU+fmxhYmVsfjwvaDU+PC90ZD4KPC90cj4KCgpbW2Zvcm0uc3VibWl0XV0KPGRpdiBjbGFzcz0idGV4dC1sZWZ0Ij4KICAgIDxidXR0b24gdHlwZT0ic3VibWl0IiBuYW1lPSJzdWJtaXQiIHZhbHVlPSJ+dmFsdWV+IiBjbGFzcz0iYnRuIGJ0bi1wcmltYXJ5IGJ0bi1+c2l6ZX4iPn5sYWJlbH48L2J1dHRvbj4KPC9kaXY+CgpbW2Zvcm0ucmVzZXRdXQo8IS0tIDxidXR0b24gdHlwZT0icmVzZXQiIGNsYXNzPSJidG4gYnRuLXByaW1hcnkgYnRuLW1kIj5SZXNldCBGb3JtPC9idXR0b24+IC0tPgoKCltbZm9ybS5idXR0b25dXQo8YSBocmVmPSJ+aHJlZn4iIGNsYXNzPSJidG4gYnRuLXByaW5hcnkgYnRuLX5zaXplfiI+fmxhYmVsfjwvYT4KCgpbW2Zvcm0uYm9vbGVhbl1dCjxkaXYgY2xhc3M9InJhZGlvZm9ybSI+CiAgICA8aW5wdXQgdHlwZT0icmFkaW8iIG5hbWU9In5uYW1lfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgdmFsdWU9IjEiIH5jaGtfeWVzfiAvPiA8c3Bhbj5ZZXM8L3NwYW4+IAogICAgPGlucHV0IHR5cGU9InJhZGlvIiBuYW1lPSJ+bmFtZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHZhbHVlPSIwIiB+Y2hrX25vfiAvPiA8c3Bhbj5Obzwvc3Bhbj4gCjwvZGl2PgoKW1tmb3JtLnNlbGVjdF1dCjxzZWxlY3QgbmFtZT0ifm5hbWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiB+d2lkdGh+IH5vbmNoYW5nZX4+CiAgICB+b3B0aW9uc34KPC9zZWxlY3Q+CgoKW1tmb3JtLnRleHRib3hdXQo8aW5wdXQgdHlwZT0ifnR5cGV+IiBuYW1lPSJ+bmFtZX4iIHZhbHVlPSJ+dmFsdWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiBpZD0ifmlkfiIgfnBsYWNlaG9sZGVyfiB+YWN0aW9uc34gfnZhbGlkYXRpb25+IC8+CgoKCltbZm9ybS50ZXh0YXJlYV1dCjx0ZXh0YXJlYSBuYW1lPSJ+bmFtZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIGlkPSJ+aWR+IiBzdHlsZT0id2lkdGg6IDEwMCUiIH5wbGFjZWhvbGRlcn4+fnZhbHVlfjwvdGV4dGFyZWE+CgoKW1tmb3JtLnBob25lXV0KPGRpdiBjbGFzcz0iZm9ybS1ncm91cCI+CiAgICA8c2VsZWN0IG5hbWU9In5uYW1lfl9jb3VudHJ5IiBjbGFzcz0iZm9ybS1jb250cm9sIGNvbC1sZy0yIj4KICAgICAgICB+Y291bnRyeV9jb2RlX29wdGlvbnN+CiAgICA8L3NlbGVjdD4gCiAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ifm5hbWV+IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImZvcm0tY29udHJvbCBjb2wtbGctMTAiICB+cGxhY2Vob2xkZXJ+Pgo8L2Rpdj4KCltbZm9ybS5hbW91bnRdXQo8c3BhbiBzdHlsZT0iZmxvYXQ6IGxlZnQ7Ij5+Y3VycmVuY3lfc2lnbn48L3NwYW4+IAo8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ifm5hbWV+IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA2MHB4OyBmbG9hdDogbGVmdDsiIH5wbGFjZWhvbGRlcn4gZGF0YS1wYXJzbGV5LXR5cGU9ImRlY2ltYWwiPgoKCltbZm9ybS5kYXRlXV0KPHNlbGVjdCBuYW1lPSJ+bmFtZX5fbW9udGgiIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogMTIwcHg7IGZsb2F0OiBsZWZ0OyI+CiAgICB+bW9udGhfb3B0aW9uc34KPC9zZWxlY3Q+IAo8c2VsZWN0IG5hbWU9In5uYW1lfl9kYXl+IiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDMwcHg7IGZsb2F0OiBsZWZ0OyI+CiAgICB+ZGF5X29wdGlvbnN+Cjwvc2VsZWN0PiwgCjxzZWxlY3QgbmFtZT0ifm5hbWV+X3llYXJ+IiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDcwcHg7IGZsb2F0OiBsZWZ0OyI+CiAgICB+eWVhcl9vcHRpb25zfgo8L3NlbGVjdD4KCltbZm9ybS50aW1lXV0KPHNlbGVjdCBuYW1lPSJ+bmFtZX5faG91ciIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA2MHB4OyBmbG9hdDogbGVmdDsiPgogICAgfmhvdXJfb3B0aW9uc34KPC9zZWxlY3Q+IDogCjxzZWxlY3QgbmFtZT0ifm5hbWV+X21pbiIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA2MHB4OyBmbG9hdDogbGVmdDsiPgogICAgfm1pbnV0ZV9vcHRpb25zfgo8L3NlbGVjdD4KCgpbW2Zvcm0uZGF0ZV9pbnRlcnZhbF1dCjxkaXYgY2xhc3M9ImZvcm0tZ3JvdXAiPgogICAgPGRpdiBjbGFzcz0iY29sLWxnLTgiIHN0eWxlPSJwYWRkaW5nLWxlZnQ6IDAiPgogICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJ+bmFtZX5fbnVtIiBjbGFzcz0iZm9ybS1jb250cm9sIiB2YWx1ZT0ifm51bX4iID4gCiAgICA8L2Rpdj4KICAgIDxkaXYgY2xhc3M9ImNvbC1sZy00IiBzdHlsZT0icGFkZGluZy1yaWdodDogMCI+CiAgICAgICAgPHNlbGVjdCBuYW1lPSJ+bmFtZX5fcGVyaW9kIiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDEwMCUiID4KICAgICAgICAgICAgfnBlcmlvZF9vcHRpb25zfgogICAgICAgIDwvc2VsZWN0PgogICAgPC9kaXY+CjwvZGl2PgoKCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpib3g+IC4uLiA8L2E6Ym94PgoqIDxhOmJveF9oZWFkZXIgdGl0bGU9Ii4uLiI+IC4uLiA8L2E6Ym94X2hlYWRlcj4KKgoqIENvbnRhaW5lcnMgLyBwYW5lbHMgdGhhdCBoZWxwIHNlcGFyYXRlIGRpZmZlcmVudCBzZWN0aW9ucyBvZiB0aGUgcGFnZS4gIENhbiBvcHRpb25hbGx5IAoqIGNvbnRhaW4gYSBoZWFkZXIgd2l0aCB0aXRsZS4KKioqKioqKioqKioqKioqKioqKioKCltbYm94XV0KPGRpdiBjbGFzcz0icGFuZWwgcGFuZWwtZGVmYXVsdCI+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1oZWFkaW5nIj4gfmJveF9oZWFkZXJ+PC9kaXY+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1ib2R5Ij4KICAgICAgICB+Y29udGVudHN+CiAgICA8L2Rpdj4KPC9kaXY+CgpbW2JveC5oZWFkZXJdXQo8c3BhbiBzdHlsZT0iYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkICMzMzMzMzM7IG1hcmdpbi1ib3R0b206IDhweDsiPgogICAgPGgzPn50aXRsZX48L2gzPgogICAgfmNvbnRlbnRzfgo8L3NwYW4+CgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTppbnB1dF9ib3g+IC4uLiA8L2E6aW5wdXRfYm94PgoqCiogTWVhbnQgZm9yIGEgZnVsbCB3aWR0aCBzaXplaCwgc2hvcnQgc2l6ZWQgYmFyLiAgVXNlZCAKKiBmb3IgdGhpbmdzIHN1Y2ggYXMgYSBzZWFyY2ggdGV4dGJveCwgb3Igb3RoZXIgYmFycyB0byBzZXBhcmF0ZSB0aGVtIGZyb20gCiogdGhlIHJlc3Qgb2YgdGhlIHBhZ2UgY29udGVudC4KKgoqIEV4YW1wbGUgb2YgdGhpcyBpcyBVc2Vycy0+TWFuYWdlIFVzZXIgbWVudSBvZiB0aGUgYWRtaW5pc3RyYXRpb24gCiogcGFuZWwsIHdoZXJlIHRoZSBzZWFyY2ggYm94IGlzIHN1cnJvdW5kZWQgYnkgYW4gaW5wdXQgYm94LgoqKioqKioqKioqKioqKioqKioqKgoKW1tpbnB1dF9ib3hdXQo8ZGl2IGNsYXNzPSJwYW5lbCBwYW5lbC1kZWZhdWx0IHNlYXJjaF91c2VyIj4KICAgIDxkaXYgY2xhc3M9InBhbmVsLWJvZHkiPgogICAgICAgIH5jb250ZW50c34KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6Y2FsbG91dHM+CioKKiBUaGUgY2FsbG91dHMgLyBpbmZvcm1hdGlvbmFsIG1lc3NhZ2VzIHRoYXQgYXJlIGRpc3BsYXllZCBvbiB0aGUgCiogdG9wIG9mIHBhZ2VzIGFmdGVyIGFuIGFjdGlvbiBpcyBwZXJmb3JtZWQuICBUaGVzZSBtZXNzYWdlcyBhcmUgCiogZm9yOiBzdWNjZXNzLCBlcnJvciwgd2FybmluZywgaW5mby4gCioKKiBUaGUgZmlyc3QgZWxlbWVudCBpcyB0aGUgSFRNTCBjb2RlIG9mIHRoZSBjYWxsb3V0cyBpdHNlbGYsIHRoZSBzZWNvbmQgCiogYW5kIHRoaXJkIGVsZW1lbnRzIGFyZSBKU09OIGVuY29kZWQgc3RyaW5ncyB0aGF0IGRlZmluZSB0aGUgCiogQ1NTIGFuZCBpY29uIGFsaWFzZXMgdG8gdXNlIGZvciBlYWNoIG1lc3NhZ2UgdHlwZS4KKioqKioqKioqKioqKioqKioqKioKCltbY2FsbG91dHNdXQo8ZGl2IGNsYXNzPSJhbGVydCBhbGVydC1+Y3NzX2FsaWFzfiI+PHA+CiAgICA8YnV0dG9uIHR5cGU9ImJ1dHRvbiIgY2xhc3M9ImNsb3NlIiBkYXRhLWRpc21pc3M9ImFsZXJ0Ij4mdGltZXM7PC9idXR0b24+CiAgICB+bWVzc2FnZXN+CjwvcD48L2Rpdj4KCgpbW2NhbGxvdXRzLmNzc11dClsKICAgICJzdWNjZXNzIjogInN1Y2Nlc3MiLCAKICAgICJlcnJvciI6ICJlcnJvciIsIAogICAgIndhcm5pbmciOiAid2FybmluZyIsIAogICAgImluZm8iOiAiaW5mbyIKXQoKCltbY2FsbG91dHMuaWNvbl1dClsKICAgICJzdWNjZXNzIjogImZhIGZhLWNoZWNrIiwgCiAgICAiZXJyb3IiOiAiZmEgZmEtYmFuIiwgCiAgICAid2FybmluZyI6ICJmYSBmYS13YXJuaW5nIiwgCiAgICAiaW5mbyI6ICJmYSBmYS1pbmZvIgpdCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpuYXZfbWVudT4KKgoqIFRoZSBuYXZpZ2F0aW9uIG1lbnUgb2YgdGhlIHRoZW1lLCBpbmNsdWRpbmcgaGVhZGVyIC8gc2VwYXJhdG9yIAoqIGl0ZW1zLCBwYXJlbnQgbWVudXMsIGFuZCBzdWJtZW51cy4KKioqKioqKioqKioqKioqKioqKioKCltbbmF2LmhlYWRlcl1dCjxsaT48aDM+fm5hbWV+PC9saT4KCgpbW25hdi5wYXJlbnRdXQo8bGk+CiAgICA8YSBocmVmPSJ+dXJsfiI+fmljb25+IH5uYW1lfjwvYT4KICAgIDx1bD4KICAgICAgICB+c3VibWVudXN+CiAgICA8L3VsPgo8L2xpPgoKCltbbmF2Lm1lbnVdXQo8bGk+PGEgaHJlZj0ifnVybH4iPn5pY29ufn5uYW1lfjwvYT48L2xpPgoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6dGFiX2NvbnRyb2w+IC4uLiA8L2E6dGFiX2NvbnRyb2w+CiogPGE6dGFiX3BhZ2UgbmFtZT0iLi4uIj4gLi4uIDwvYTp0YWJfcGFnZT4KKgoqIFRoZSB0YWIgY29udHJvbHMuICBJbmNsdWRlcyB0aGUgdGFiIGNvbnRyb2wgaXRzZWxmLCBuYXYgaXRlbXMgYW5kIAoqIHRoZSBib2R5IHBhbmUgb2YgdGFiIHBhZ2VzLgoqKioqKioqKioqKioqKioqKioqKgoKW1t0YWJfY29udHJvbF1dCgo8ZGl2IGNsYXNzPSJuYXYtdGFicy1jdXN0b20iPgogICAgPHVsIGNsYXNzPSJuYXYgbmF2LXRhYnMiPgogICAgICAgIH5uYXZfaXRlbXN+CiAgICA8L3VsPgoKICAgIDxkaXYgY2xhc3M9InRhYi1jb250ZW50Ij4KICAgICAgICB+dGFiX3BhZ2VzfgogICAgPC9kaXY+CjwvZGl2PgoKCgpbW3RhYl9jb250cm9sLm5hdl9pdGVtXV0KPGxpIGNsYXNzPSJ+YWN0aXZlfiI+PGEgaHJlZj0iI3RhYn50YWJfbnVtfiIgZGF0YS10b2dnbGU9InRhYiI+fm5hbWV+PC9hPjwvbGk+CgoKW1t0YWJfY29udHJvbC5wYWdlXV0KPGRpdiBjbGFzcz0idGFiLXBhbmUgfmFjdGl2ZX4iIGlkPSJ0YWJ+dGFiX251bX4iPgogICAgfmNvbnRlbnRzfgo8L2Rpdj4KCgpbW3RhYl9jb250cm9sLmNzc19hY3RpdmVdXQphY3RpdmUKCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpkYXRhX3RhYmxlPiAuLi4gPC9hOmRhdGFfdGFibGU+CiogPGE6dGFibGVfc2VhcmNoX2Jhcj4KKiA8YTp0aD4gLi4uIDxhOnRoPgoqIDxhOnRyPiAuLi4gPC9hOnRyPgoqCiogVGhlIGRhdGEgdGFibGVzIHVzZWQgdGhyb3VnaG91dCB0aGUgc29mdHdhcmUuCioqKioqKioqKioqKioqKioqKioqCgpbW2RhdGFfdGFibGVdXQo8dGFibGUgY2xhc3M9InRhYmxlIHRhYmxlLWJvcmRlcmVkIHRhYmxlLXN0cmlwZWQgdGFibGUtaG92ZXIiIGlkPSJ+dGFibGVfaWR+Ij4KICAgIDx0aGVhZD4KICAgICAgICB+c2VhcmNoX2Jhcn4KICAgIDx0cj4KICAgICAgICB+aGVhZGVyX2NlbGxzfgogICAgPC90cj4KICAgIDwvdGhlYWQ+CgogICAgPHRib2R5IGlkPSJ+dGFibGVfaWR+X3Rib2R5IiBjbGFzcz0iYm9keXRhYmxlIj4KICAgICAgICB+dGFibGVfYm9keX4KICAgIDwvdGJvZHk+CgogICAgPHRmb290Pjx0cj4KICAgICAgICA8dGQgY29sc3Bhbj0ifnRvdGFsX2NvbHVtbnN+IiBhbGlnbj0icmlnaHQiPgogICAgICAgICAgICB+ZGVsZXRlX2J1dHRvbn4KICAgICAgICAgICAgfnBhZ2luYXRpb25+CiAgICAgICAgPC90ZD4KICAgIDwvdHI+PC90Zm9vdD4KPC90YWJsZT4KCgpbW2RhdGFfdGFibGUudGhdXQo8dGggY2xhc3M9ImJveGhlYWRlciI+IDxzcGFuPn5uYW1lfjwvc3Bhbj4gfnNvcnRfZGVzY34gfnNvcnRfYXNjfjwvdGg+CgoKW1tkYXRhX3RhYmxlLnNvcnRfYXNjXV0KPGEgaHJlZj0iamF2YXNjcmlwdDphamF4X3NlbmQoJ2NvcmUvc29ydF90YWJsZScsICd+YWpheF9kYXRhfiZzb3J0X2NvbD1+Y29sX2FsaWFzfiZzb3J0X2Rpcj1hc2MnLCAnbm9uZScpOyIgYm9yZGVyPSIwIiB0aXRsZT0iU29ydCBBc2NlbmRpbmcgfmNvbF9hbGlhc34iIGNsYXNzPSJhc2MiPgogICAgPGkgY2xhc3M9ImZhIGZhLXNvcnQtYXNjIj48L2k+CjwvYT4KCgpbW2RhdGFfdGFibGUuc29ydF9kZXNjXV0KPGEgaHJlZj0iamF2YXNjcmlwdDphamF4X3NlbmQoJ2NvcmUvc29ydF90YWJsZScsICd+YWpheF9kYXRhfiZzb3J0X2NvbD1+Y29sX2FsaWFzfiZzb3J0X2Rpcj1kZXNjJywgJ25vbmUnKTsiIGJvcmRlcj0iMCIgdGl0bGU9IlNvcnQgRGVjZW5kaW5nIH5jb2xfYWxpYXN+IiBjbGFzcz0iZGVzYyI+CiAgICA8aSBjbGFzcz0iZmEgZmEtc29ydC1kZXNjIj48L2k+CjwvYT4KCgpbW2RhdGFfdGFibGUuc2VhcmNoX2Jhcl1dCjx0cj4KICAgIDx0ZCBzdHlsZT0iYm9yZGVyLXRvcDoxcHggc29saWQgI2NjYyIgY29sc3Bhbj0ifnRvdGFsX2NvbHVtbnN+IiBhbGlnbj0icmlnaHQiPgogICAgICAgIDxkaXYgY2xhc3M9ImZvcm1zZWFyY2giPgogICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ic2VhcmNoX350YWJsZV9pZH4iIHBsYWNlaG9sZGVyPSJ+c2VhcmNoX2xhYmVsfi4uLiIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiAyMTBweDsiPiAKICAgICAgICAgICAgPGEgaHJlZj0iamF2YXNjcmlwdDphamF4X3NlbmQoJ2NvcmUvc2VhcmNoX3RhYmxlJywgJ35hamF4X2RhdGF+JywgJ3NlYXJjaF9+dGFibGVfaWR+Jyk7IiBjbGFzcz0iYnRuIGJ0bi1wcmltYXJ5IGJ0bi1tZCI+PGkgY2xhc3M9ImZhIGZhLXNlYXJjaCI+PC9pPjwvYT4KICAgICAgICA8L2Rpdj4KICAgIDwvdGQ+CjwvdHI+CgoKW1tkYXRhX3RhYmxlLmRlbGV0ZV9idXR0b25dXQo8YSBocmVmPSJqYXZhc2NyaXB0OmFqYXhfY29uZmlybSgnQXJlIHlvdSBzdXJlIHlvdSB3YW50IHRvIGRlbGV0ZSB0aGUgY2hlY2tlZCByZWNvcmRzPycsICdjb3JlL2RlbGV0ZV9yb3dzJywgJ35hamF4X2RhdGF+JywgJycpOyIgY2xhc3M9ImJ0biBidG4tcHJpbWFyeSBidG4tbWQgYm90b250ZXN0IiBzdHlsZT0iZmxvYXQ6IGxlZnQ7Ij5+ZGVsZXRlX2J1dHRvbl9sYWJlbH48L2E+CgoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6cGFnaW5hdGlvbj4KKgoqIFBhZ2luYXRpb24gbGlua3MsIGdlbmVyYWxseSBkaXNwbGF5ZWQgYXQgdGhlIGJvdHRvbSBvZiAKKiBkYXRhIHRhYmxlcywgYnV0IGNhbiBiZSB1c2VkIGFueXdoZXJlLgoqKioqKioqKioqKioqKioqKioqKgoKW1twYWdpbmF0aW9uXV0KPHNwYW4gaWQ9InBnbnN1bW1hcnlffmlkfiIgc3R5bGU9InZlcnRpY2FsLWFsaWduOiBtaWRkbGU7IGZvbnQtc2l6ZTogOHB0OyBtYXJnaW4tcmlnaHQ6IDdweDsiPgogICAgPGI+fnN0YXJ0X3JlY29yZH4gLSB+ZW5kX3JlY29yZH48L2I+IG9mIDxiPn50b3RhbF9yZWNvcmRzfjwvYj4KPC9zcGFuPgoKPHVsIGNsYXNzPSJwYWdpbmF0aW9uIiBpZCA9InBnbl9+aWR+Ij4KICAgIH5pdGVtc34KPC91bD4KCgpbW3BhZ2VpbmF0aW9uLml0ZW1dXQo8bGkgc3R5bGU9ImRpc3BsYXk6IH5kaXNwbGF5fjsiPjxhIGhyZWY9In51cmx+Ij5+bmFtZX48L2E+PC9saT4KCltbcGFnaW5hdGlvbi5hY3RpdmVfaXRlbV1dCjxsaSBjbGFzcz0iYWN0aXZlIj48YT5+cGFnZX48L2E+PC9saT4KCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpkcm9wZG93bl9hbGVydHM+CiogPGE6ZHJvcGRvd25fbWVzc2FnZXM+CioKKiBUaGUgbGlzdCBpdGVtcyB1c2VkIGZvciB0aGUgdHdvIGRyb3AgZG93biBsaXN0cywgbm90aWZpY2F0aW9ucyAvIGFsZXJ0cyBhbmQgCiogbWVzc2FnZXMuICBUaGVzZSBhcmUgZ2VuZXJhbGx5IGRpc3BsYXllZCBpbiB0aGUgdG9wIHJpZ2h0IGNvcm5lciAKKiBvZiBhZG1pbiBwYW5lbCAvIG1lbWJlciBhcmVhIHRoZW1lcy4KKioqKioqKioqKioqKioqKioqKioKCgpbW2Ryb3Bkb3duLmFsZXJ0XV0KPGxpIGNsYXNzPSJtZWRpYSI+CiAgICA8ZGl2IGNsYXNzPSJtZWRpYS1ib2R5Ij4KICAgICAgICA8YSBocmVmPSJ+dXJsfiI+fm1lc3NhZ2V+CiAgICAgICAgPGRpdiBjbGFzcz0idGV4dC1tdXRlZCBmb250LXNpemUtc20iPn50aW1lfjwvZGl2PgoJPC9hPgogICAgPC9kaXY+CjwvbGk+CgoKW1tkcm9wZG93bi5tZXNzYWdlXV0KPGxpIGNsYXNzPSJtZWRpYSI+CiAgICA8ZGl2IGNsYXNzPSJtZWRpYS1ib2R5Ij4KCiAgICAgICAgPGRpdiBjbGFzcz0ibWVkaWEtdGl0bGUiPgogICAgICAgICAgICA8YSBocmVmPSJ+dXJsfiI+CiAgICAgICAgICAgICAgICA8c3BhbiBjbGFzcz0iZm9udC13ZWlnaHQtc2VtaWJvbGQiPn5mcm9tfjwvc3Bhbj4KICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJ0ZXh0LW11dGVkIGZsb2F0LXJpZ2h0IGZvbnQtc2l6ZS1zbSI+fnRpbWV+PC9zcGFuPgogICAgICAgICAgICA8L2E+CiAgICAgICAgPC9kaXY+CgogICAgICAgIDxzcGFuIGNsYXNzPSJ0ZXh0LW11dGVkIj5+bWVzc2FnZX48L3NwYW4+CiAgICA8L2Rpdj4KPC9saT4KCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpib3hsaXN0cz4KKgoqIFRoZSBib3hsaXN0cyBhcyBzZWVuIG9uIHBhZ2VzIHN1Y2ggYXMgU2V0dGluZ3MtPlVzZXJzLiAgVXNlZCB0byAKKiBkaXNwbGF5IGxpbmtzIHRvIG11bHRpcGxlIHBhZ2VzIHdpdGggZGVzY3JpcHRpb25zLgoqKioqKioqKioqKioqKioqKioqKgoKW1tib3hsaXN0XV0KPHVsIGNsYXNzPSJib3hsaXN0Ij4KICAgIH5pdGVtc34KPC91bD4KCgoKW1tib3hsaXN0Lml0ZW1dXQo8bGk+CiAgICA8YSBocmVmPSJ+dXJsfiI+CiAgICAgICAgPGI+fnRpdGxlfjwvYj48YnIgLz4KICAgICAgICB+ZGVzY3JpcHRpb25+CiAgICA8L2E+CjwvbGk+CgoKKioqKioqKioqKioqKioqKioqKioKKiBkYXNoYm9hcmQKKgoqIEhUTUwgc25pcHBldHMgZm9yIHRoZSBkYXNoYm9hcmQgd2lkZ2V0cy4KKioqKioqKioqKioqKioqKioqKioKCltbZGFzaGJvYXJkXV0KCjxkaXYgY2xhc3M9InJvdyBib3hncmFmIj4KICAgIH50b3BfaXRlbXN+CjwvZGl2PgoKPGRpdiBjbGFzcz0icGFuZWwgcGFuZWwtZmxhdCI+CiAgICA8YTpmdW5jdGlvbiBhbGlhcz0iZGlzcGxheV90YWJjb250cm9sIiB0YWJjb250cm9sPSJjb3JlOmRhc2hib2FyZCI+CjwvZGl2PgoKPGRpdiBjbGFzcz0ic2lkZWJhciBzaWRlYmFyLWxpZ2h0IGJnLXRyYW5zcGFyZW50IHNpZGViYXItY29tcG9uZW50IHNpZGViYXItY29tcG9uZW50LXJpZ2h0IGJvcmRlci0wIHNoYWRvdy0wIG9yZGVyLTEgb3JkZXItbWQtMiBzaWRlYmFyLWV4cGFuZC1tZCI+CiAgICA8ZGl2IGNsYXNzPSJzaWRlYmFyLWNvbnRlbnQiPgogICAgICAgIH5yaWdodF9pdGVtc34KICAgIDwvZGl2Pgo8L2Rpdj4KCltbZGFzaGJvYXJkLnRvcF9pdGVtXV0KPGRpdiBjbGFzcz0iY29sLWxnLTQiPgogICAgPGRpdiBjbGFzcz0ifnBhbmVsX2NsYXNzfiI+CiAgICAgICAgPGRpdiBjbGFzcz0icGFuZWwtYm9keSI+CgogICAgICAgICAgICA8aDMgY2xhc3M9Im5vLW1hcmdpbiI+Myw0NTA8L2gzPgogICAgICAgICAgICAgICAgfnRpdGxlfgogICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0idGV4dC1tdXRlZCB0ZXh0LXNpemUtc21hbGwiPn5jb250ZW50c348L2Rpdj4KICAgICAgICA8L2Rpdj4KICAgICAgICA8ZGl2IGNsYXNzPSJjb250YWluZXItZmx1aWQiPgogICAgICAgICAgICA8ZGl2IGlkPSJ+ZGl2aWR+Ij48L2Rpdj4KICAgICAgICA8L2Rpdj4KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKW1tkYXNoYm9hcmQucmlnaHRfaXRlbV1dCjxkaXYgY2xhc3M9ImNhcmQiPgogICAgPGRpdiBjbGFzcz0iY2FyZC1oZWFkZXIgYmctdHJhbnNwYXJlbnQgaGVhZGVyLWVsZW1lbnRzLWlubGluZSI+CiAgICAgICAgPHNwYW4gY2xhc3M9ImNhcmQtdGl0bGUgZm9udC13ZWlnaHQtc2VtaWJvbGQiPn50aXRsZX48L3NwYW4+CiAgICA8L2Rpdj4KICAgIDxkaXYgY2xhc3M9ImNhcmQtYm9keSI+CiAgICAgICAgPHVsIGNsYXNzPSJtZWRpYS1saXN0Ij4KICAgICAgICAgICAgPGxpIGNsYXNzPSJtZWRpYSI+CiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJtZWRpYS1ib2R5Ij4KICAgICAgICAgICAgICAgICAgICB+Y29udGVudHN+CiAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPC9saT4KICAgICAgICA8L3VsPgogICAgPC9kaXY+CjwvZGl2PgoKCgoK'
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


}

