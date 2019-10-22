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
        'tags.tpl' => 'CioqKioqKioqKioqKioqKioqKioqKioqKioqKioqKgoqIFRoaXMgZmlsZSBjb250YWlucyBhbGwgSFRNTCBzbmlwcGV0cyBmb3IgdGhlIHNwZWNpYWwgCiogSFRNTCB0YWdzIHRoYXQgYXJlIHVzZWQgdGhyb3VnaG91dCBBcGV4LiAgVGhlc2UgYXJlIHRhZ3MgcHJlZml4ZWQgd2l0aCAiYToiLCBzdWNoIGFzIAoqIDxhOmJveD4sIDxhOmZvcm1fdGFibGU+LCBhbmQgb3RoZXJzLgoqCiogQmVsb3cgYXJlIGxpbmVzIHdpdGggdGhlIGZvcm1hdCAiW1t0YWdfbmFtZV1dIiwgYW5kIGV2ZXJ5dGhpbmcgYmVsb3cgdGhhdCAKKiBsaW5lIHJlcHJlc2VudHMgdGhlIGNvbnRlbnRzIG9mIHRoYXQgSFRNTCB0YWcsIHVudGlsIHRoZSBuZXh0IG9jY3VycmVuY2Ugb2YgIltbdGFnX25hbWVdXSIgaXMgcmVhY2hlZC4KKgoqIFRhZyBuYW1lcyB0aGF0IGNvbnRhaW4gYSBwZXJpb2QgKCIuIikgc2lnbmlmeSBhIGNoaWxkIGl0ZW0sIGFzIHlvdSB3aWxsIG5vdGljZSBiZWxvdy4KKgoqKioqKioqKioqKioqKioqKioqKioqKioqKioqKioKCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpmb3JtX3RhYmxlPiAuLi4gPC9hOmZvcm1fdGFibGU+CiogPGE6Rk9STV9GSUVMRD4KKgoqIFRoZSBmb3JtIHRhYmxlLCBhbmQgdmFyaW91cyBmb3JtIGZpZWxkIGVsZW1lbnRzCioqKioqKioqKioqKioqKioqKioqCgpbW2Zvcm1fdGFibGVdXQo8dGFibGUgYm9yZGVyPSIwIiBjbGFzcz0iZm9ybV90YWJsZSIgc3R5bGU9IndpZHRoOiB+d2lkdGh+OyBhbGlnbjogfmFsaWdufjsiPgogICAgfmNvbnRlbnRzfgo8L3RhYmxlPgoKCltbZm9ybV90YWJsZS5yb3ddXQo8dHI+CiAgICA8dGQ+PGxhYmVsIGZvcj0ifm5hbWV+Ij5+bGFiZWx+OjwvbGFiZWw+PC90ZD4KICAgIDx0ZD48ZGl2IGNsYXNzPSJmb3JtLWdyb3VwIj4KICAgICAgICB+Zm9ybV9maWVsZH4KICAgIDwvZGl2PjwvdGQ+CjwvdHI+CgoKW1tmb3JtX3RhYmxlLnNlcGFyYXRvcl1dCjx0cj4KICAgIDx0ZCBjb2xzcGFuPSIyIj48aDU+fmxhYmVsfjwvaDU+PC90ZD4KPC90cj4KCgpbW2Zvcm0uc3VibWl0XV0KPGRpdiBjbGFzcz0idGV4dC1sZWZ0Ij4KICAgIDxidXR0b24gdHlwZT0ic3VibWl0IiBuYW1lPSJzdWJtaXQiIHZhbHVlPSJ+dmFsdWV+IiBjbGFzcz0iYnRuIGJ0bi1wcmltYXJ5IGJ0bi1+c2l6ZX4iPn5sYWJlbH48L2J1dHRvbj4KPC9kaXY+CgpbW2Zvcm0ucmVzZXRdXQo8IS0tIDxidXR0b24gdHlwZT0icmVzZXQiIGNsYXNzPSJidG4gYnRuLXByaW1hcnkgYnRuLW1kIj5SZXNldCBGb3JtPC9idXR0b24+IC0tPgoKCltbZm9ybS5idXR0b25dXQo8YSBocmVmPSJ+aHJlZn4iIGNsYXNzPSJidG4gYnRuLXByaW5hcnkgYnRuLX5zaXplfiI+fmxhYmVsfjwvYT4KCgpbW2Zvcm0uYm9vbGVhbl1dCjxkaXYgY2xhc3M9InJhZGlvZm9ybSI+CiAgICA8aW5wdXQgdHlwZT0icmFkaW8iIG5hbWU9In5uYW1lfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgdmFsdWU9IjEiIH5jaGtfeWVzfiAvPiA8c3Bhbj5ZZXM8L3NwYW4+IAogICAgPGlucHV0IHR5cGU9InJhZGlvIiBuYW1lPSJ+bmFtZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHZhbHVlPSIwIiB+Y2hrX25vfiAvPiA8c3Bhbj5Obzwvc3Bhbj4gCjwvZGl2PgoKW1tmb3JtLnNlbGVjdF1dCjxzZWxlY3QgbmFtZT0ifm5hbWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiB+d2lkdGh+IH5vbmNoYW5nZX4+CiAgICB+b3B0aW9uc34KPC9zZWxlY3Q+CgoKW1tmb3JtLnRleHRib3hdXQo8aW5wdXQgdHlwZT0ifnR5cGV+IiBuYW1lPSJ+bmFtZX4iIHZhbHVlPSJ+dmFsdWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiBpZD0ifmlkfiIgfnBsYWNlaG9sZGVyfiB+YWN0aW9uc34gfnZhbGlkYXRpb25+IC8+CgoKCltbZm9ybS50ZXh0YXJlYV1dCjx0ZXh0YXJlYSBuYW1lPSJ+bmFtZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIGlkPSJ+aWR+IiBzdHlsZT0id2lkdGg6IDEwMCUiIH5wbGFjZWhvbGRlcn4+fnZhbHVlfjwvdGV4dGFyZWE+CgoKW1tmb3JtLnBob25lXV0KPGRpdiBjbGFzcz0iZm9ybS1ncm91cCI+CiAgICA8c2VsZWN0IG5hbWU9In5uYW1lfl9jb3VudHJ5IiBjbGFzcz0iZm9ybS1jb250cm9sIGNvbC1sZy0yIj4KICAgICAgICB+Y291bnRyeV9jb2RlX29wdGlvbnN+CiAgICA8L3NlbGVjdD4gCiAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ifm5hbWV+IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImZvcm0tY29udHJvbCBjb2wtbGctMTAiICB+cGxhY2Vob2xkZXJ+Pgo8L2Rpdj4KCltbZm9ybS5hbW91bnRdXQo8c3BhbiBzdHlsZT0iZmxvYXQ6IGxlZnQ7Ij5+Y3VycmVuY3lfc2lnbn48L3NwYW4+IAo8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ifm5hbWV+IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA2MHB4OyBmbG9hdDogbGVmdDsiIH5wbGFjZWhvbGRlcn4gZGF0YS1wYXJzbGV5LXR5cGU9ImRlY2ltYWwiPgoKCltbZm9ybS5kYXRlXV0KPHNlbGVjdCBuYW1lPSJ+bmFtZX5fbW9udGgiIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogMTIwcHg7IGZsb2F0OiBsZWZ0OyI+CiAgICB+bW9udGhfb3B0aW9uc34KPC9zZWxlY3Q+IAo8c2VsZWN0IG5hbWU9In5uYW1lfl9kYXl+IiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDMwcHg7IGZsb2F0OiBsZWZ0OyI+CiAgICB+ZGF5X29wdGlvbnN+Cjwvc2VsZWN0PiwgCjxzZWxlY3QgbmFtZT0ifm5hbWV+X3llYXJ+IiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDcwcHg7IGZsb2F0OiBsZWZ0OyI+CiAgICB+eWVhcl9vcHRpb25zfgo8L3NlbGVjdD4KCltbZm9ybS50aW1lXV0KPHNlbGVjdCBuYW1lPSJ+bmFtZX5faG91ciIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA2MHB4OyBmbG9hdDogbGVmdDsiPgogICAgfmhvdXJfb3B0aW9uc34KPC9zZWxlY3Q+IDogCjxzZWxlY3QgbmFtZT0ifm5hbWV+X21pbiIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA2MHB4OyBmbG9hdDogbGVmdDsiPgogICAgfm1pbnV0ZV9vcHRpb25zfgo8L3NlbGVjdD4KCgpbW2Zvcm0uZGF0ZV9pbnRlcnZhbF1dCjxkaXYgY2xhc3M9ImZvcm0tZ3JvdXAiPgogICAgPGRpdiBjbGFzcz0iY29sLWxnLTgiIHN0eWxlPSJwYWRkaW5nLWxlZnQ6IDAiPgogICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJ+bmFtZX5fbnVtIiBjbGFzcz0iZm9ybS1jb250cm9sIiB2YWx1ZT0ifm51bX4iID4gCiAgICA8L2Rpdj4KICAgIDxkaXYgY2xhc3M9ImNvbC1sZy00IiBzdHlsZT0icGFkZGluZy1yaWdodDogMCI+CiAgICAgICAgPHNlbGVjdCBuYW1lPSJ+bmFtZX5fcGVyaW9kIiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDEwMCUiID4KICAgICAgICAgICAgfnBlcmlvZF9vcHRpb25zfgogICAgICAgIDwvc2VsZWN0PgogICAgPC9kaXY+CjwvZGl2PgoKCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpib3g+IC4uLiA8L2E6Ym94PgoqIDxhOmJveF9oZWFkZXIgdGl0bGU9Ii4uLiI+IC4uLiA8L2E6Ym94X2hlYWRlcj4KKgoqIENvbnRhaW5lcnMgLyBwYW5lbHMgdGhhdCBoZWxwIHNlcGFyYXRlIGRpZmZlcmVudCBzZWN0aW9ucyBvZiB0aGUgcGFnZS4gIENhbiBvcHRpb25hbGx5IAoqIGNvbnRhaW4gYSBoZWFkZXIgd2l0aCB0aXRsZS4KKioqKioqKioqKioqKioqKioqKioKCltbYm94XV0KPGRpdiBjbGFzcz0icGFuZWwgcGFuZWwtZGVmYXVsdCI+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1oZWFkaW5nIj4gfmJveF9oZWFkZXJ+PC9kaXY+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1ib2R5Ij4KICAgICAgICB+Y29udGVudHN+CiAgICA8L2Rpdj4KPC9kaXY+CgpbW2JveC5oZWFkZXJdXQo8c3BhbiBzdHlsZT0iYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkICMzMzMzMzM7IG1hcmdpbi1ib3R0b206IDhweDsiPgogICAgPGgzPn50aXRsZX48L2gzPgogICAgfmNvbnRlbnRzfgo8L3NwYW4+CgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTppbnB1dF9ib3g+IC4uLiA8L2E6aW5wdXRfYm94PgoqCiogTWVhbnQgZm9yIGEgZnVsbCB3aWR0aCBzaXplaCwgc2hvcnQgc2l6ZWQgYmFyLiAgVXNlZCAKKiBmb3IgdGhpbmdzIHN1Y2ggYXMgYSBzZWFyY2ggdGV4dGJveCwgb3Igb3RoZXIgYmFycyB0byBzZXBhcmF0ZSB0aGVtIGZyb20gCiogdGhlIHJlc3Qgb2YgdGhlIHBhZ2UgY29udGVudC4KKgoqIEV4YW1wbGUgb2YgdGhpcyBpcyBVc2Vycy0+TWFuYWdlIFVzZXIgbWVudSBvZiB0aGUgYWRtaW5pc3RyYXRpb24gCiogcGFuZWwsIHdoZXJlIHRoZSBzZWFyY2ggYm94IGlzIHN1cnJvdW5kZWQgYnkgYW4gaW5wdXQgYm94LgoqKioqKioqKioqKioqKioqKioqKgoKW1tpbnB1dF9ib3hdXQo8ZGl2IGNsYXNzPSJwYW5lbCBwYW5lbC1kZWZhdWx0IHNlYXJjaF91c2VyIj4KICAgIDxkaXYgY2xhc3M9InBhbmVsLWJvZHkiPgogICAgICAgIH5jb250ZW50c34KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKCioqKioqKioqKioqKioqKioqKioqCiogPGE6Y2FsbG91dHM+CioKKiBUaGUgY2FsbG91dHMgLyBpbmZvcm1hdGlvbmFsIG1lc3NhZ2VzIHRoYXQgYXJlIGRpc3BsYXllZCBvbiB0aGUgCiogdG9wIG9mIHBhZ2VzIGFmdGVyIGFuIGFjdGlvbiBpcyBwZXJmb3JtZWQuICBUaGVzZSBtZXNzYWdlcyBhcmUgCiogZm9yOiBzdWNjZXNzLCBlcnJvciwgd2FybmluZywgaW5mby4gCioKKiBUaGUgZmlyc3QgZWxlbWVudCBpcyB0aGUgSFRNTCBjb2RlIG9mIHRoZSBjYWxsb3V0cyBpdHNlbGYsIHRoZSBzZWNvbmQgCiogYW5kIHRoaXJkIGVsZW1lbnRzIGFyZSBKU09OIGVuY29kZWQgc3RyaW5ncyB0aGF0IGRlZmluZSB0aGUgCiogQ1NTIGFuZCBpY29uIGFsaWFzZXMgdG8gdXNlIGZvciBlYWNoIG1lc3NhZ2UgdHlwZS4KKioqKioqKioqKioqKioqKioqKioKCltbY2FsbG91dHNdXQo8ZGl2IGNsYXNzPSJjYWxsb3V0IGNhbGxvdXQtfmNzc19hbGlhc34gdGV4dC1jZW50ZXIgc3VjY2VzcyI+PHA+CiAgICA8aSBjbGFzcz0iaWNvbiB+aWNvbn4iPjwvaT4KICAgIH5tZXNzYWdlc34KPC9wPjwvZGl2PgoKCltbY2FsbG91dHMuY3NzXV0KWwogICAgInN1Y2Nlc3MiOiAic3VjY2VzcyIsIAogICAgImVycm9yIjogImRhbmdlciIsIAogICAgIndhcm5pbmciOiAid2FybmluZyIsIAogICAgImluZm8iOiAiaW5mbyIKXQoKCltbY2FsbG91dHMuaWNvbl1dClsKICAgICJzdWNjZXNzIjogImZhIGZhLWNoZWNrIiwgCiAgICAiZXJyb3IiOiAiZmEgZmEtYmFuIiwgCiAgICAid2FybmluZyI6ICJmYSBmYS13YXJuaW5nIiwgCiAgICAiaW5mbyI6ICJmYSBmYS1pbmZvIgpdCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpuYXZfbWVudT4KKgoqIFRoZSBuYXZpZ2F0aW9uIG1lbnUgb2YgdGhlIHRoZW1lLCBpbmNsdWRpbmcgaGVhZGVyIC8gc2VwYXJhdG9yIAoqIGl0ZW1zLCBwYXJlbnQgbWVudXMsIGFuZCBzdWJtZW51cy4KKioqKioqKioqKioqKioqKioqKioKCltbbmF2LmhlYWRlcl1dCjxsaSBjbGFzcz0ibmF2LWl0ZW0taGVhZGVyIj48ZGl2IGNsYXNzPSJ0ZXh0LXVwcGVyY2FzZSBmb250LXNpemUteHMgbGluZS1oZWlnaHQteHMiPn5uYW1lfjwvZGl2PiA8aSBjbGFzcz0iaWNvbi1tZW51IiB0aXRsZT0ifm5hbWV+Ij48L2k+PC9saT4KCgpbW25hdi5wYXJlbnRdXQo8bGkgY2xhc3M9Im5hdi1pdGVtIG5hdi1pdGVtLXN1Ym1lbnUiPgogICAgPGEgaHJlZj0ifnVybH4iIGNsYXNzPSJuYXYtbGluayI+fmljb25+IDxzcGFuPn5uYW1lfjwvc3Bhbj48L2E+CiAgICA8dWwgY2xhc3M9Im5hdiBuYXYtZ3JvdXAtc3ViIiBkYXRhLXN1Ym1lbnUtdGl0bGU9In5uYW1lfiI+CiAgICAgICAgfnN1Ym1lbnVzfgogICAgPC91bD4KPC9saT4KCgpbW25hdi5tZW51XV0KPGxpIGNsYXNzPSJuYXYtaXRlbSI+PGEgaHJlZj0ifnVybH4iIGNsYXNzPSJuYXYtbGluayI+fmljb25+fm5hbWV+PC9hPjwvbGk+CgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTp0YWJfY29udHJvbD4gLi4uIDwvYTp0YWJfY29udHJvbD4KKiA8YTp0YWJfcGFnZSBuYW1lPSIuLi4iPiAuLi4gPC9hOnRhYl9wYWdlPgoqCiogVGhlIHRhYiBjb250cm9scy4gIEluY2x1ZGVzIHRoZSB0YWIgY29udHJvbCBpdHNlbGYsIG5hdiBpdGVtcyBhbmQgCiogdGhlIGJvZHkgcGFuZSBvZiB0YWIgcGFnZXMuCioqKioqKioqKioqKioqKioqKioqCgpbW3RhYl9jb250cm9sXV0KCjxkaXYgY2xhc3M9Im5hdi10YWJzLWN1c3RvbSI+CiAgICA8dWwgY2xhc3M9Im5hdiBuYXYtdGFicyI+CiAgICAgICAgfm5hdl9pdGVtc34KICAgIDwvdWw+CgogICAgPGRpdiBjbGFzcz0idGFiLWNvbnRlbnQiPgogICAgICAgIH50YWJfcGFnZXN+CiAgICA8L2Rpdj4KPC9kaXY+CgoKCltbdGFiX2NvbnRyb2wubmF2X2l0ZW1dXQo8bGkgY2xhc3M9In5hY3RpdmV+Ij48YSBocmVmPSIjdGFifnRhYl9udW1+IiBkYXRhLXRvZ2dsZT0idGFiIj5+bmFtZX48L2E+PC9saT4KCgpbW3RhYl9jb250cm9sLnBhZ2VdXQo8ZGl2IGNsYXNzPSJ0YWItcGFuZSB+YWN0aXZlfiIgaWQ9InRhYn50YWJfbnVtfiI+CiAgICB+Y29udGVudHN+CjwvZGl2PgoKCltbdGFiX2NvbnRyb2wuY3NzX2FjdGl2ZV1dCmFjdGl2ZQoKCgoqKioqKioqKioqKioqKioqKioqKgoqIDxhOmRhdGFfdGFibGU+IC4uLiA8L2E6ZGF0YV90YWJsZT4KKiA8YTp0YWJsZV9zZWFyY2hfYmFyPgoqIDxhOnRoPiAuLi4gPGE6dGg+CiogPGE6dHI+IC4uLiA8L2E6dHI+CioKKiBUaGUgZGF0YSB0YWJsZXMgdXNlZCB0aHJvdWdob3V0IHRoZSBzb2Z0d2FyZS4KKioqKioqKioqKioqKioqKioqKioKCltbZGF0YV90YWJsZV1dCjx0YWJsZSBjbGFzcz0idGFibGUgdGFibGUtYm9yZGVyZWQgdGFibGUtc3RyaXBlZCB0YWJsZS1ob3ZlciIgaWQ9In50YWJsZV9pZH4iPgogICAgPHRoZWFkPgogICAgICAgIH5zZWFyY2hfYmFyfgogICAgPHRyPgogICAgICAgIH5oZWFkZXJfY2VsbHN+CiAgICA8L3RyPgogICAgPC90aGVhZD4KCiAgICA8dGJvZHkgaWQ9In50YWJsZV9pZH5fdGJvZHkiIGNsYXNzPSJib2R5dGFibGUiPgogICAgICAgIH50YWJsZV9ib2R5fgogICAgPC90Ym9keT4KCiAgICA8dGZvb3Q+PHRyPgogICAgICAgIDx0ZCBjb2xzcGFuPSJ+dG90YWxfY29sdW1uc34iIGFsaWduPSJyaWdodCI+CiAgICAgICAgICAgIH5kZWxldGVfYnV0dG9ufgogICAgICAgICAgICB+cGFnaW5hdGlvbn4KICAgICAgICA8L3RkPgogICAgPC90cj48L3Rmb290Pgo8L3RhYmxlPgoKCltbZGF0YV90YWJsZS50aF1dCjx0aCBjbGFzcz0iYm94aGVhZGVyIj4gPHNwYW4+fm5hbWV+PC9zcGFuPiB+c29ydF9kZXNjfiB+c29ydF9hc2N+PC90aD4KCgpbW2RhdGFfdGFibGUuc29ydF9hc2NdXQo8YSBocmVmPSJqYXZhc2NyaXB0OmFqYXhfc2VuZCgnY29yZS9zb3J0X3RhYmxlJywgJ35hamF4X2RhdGF+JnNvcnRfY29sPX5jb2xfYWxpYXN+JnNvcnRfZGlyPWFzYycsICdub25lJyk7IiBib3JkZXI9IjAiIHRpdGxlPSJTb3J0IEFzY2VuZGluZyB+Y29sX2FsaWFzfiIgY2xhc3M9ImFzYyI+CiAgICA8aSBjbGFzcz0iZmEgZmEtc29ydC1hc2MiPjwvaT4KPC9hPgoKCltbZGF0YV90YWJsZS5zb3J0X2Rlc2NdXQo8YSBocmVmPSJqYXZhc2NyaXB0OmFqYXhfc2VuZCgnY29yZS9zb3J0X3RhYmxlJywgJ35hamF4X2RhdGF+JnNvcnRfY29sPX5jb2xfYWxpYXN+JnNvcnRfZGlyPWRlc2MnLCAnbm9uZScpOyIgYm9yZGVyPSIwIiB0aXRsZT0iU29ydCBEZWNlbmRpbmcgfmNvbF9hbGlhc34iIGNsYXNzPSJkZXNjIj4KICAgIDxpIGNsYXNzPSJmYSBmYS1zb3J0LWRlc2MiPjwvaT4KPC9hPgoKCltbZGF0YV90YWJsZS5zZWFyY2hfYmFyXV0KPHRyPgogICAgPHRkIHN0eWxlPSJib3JkZXItdG9wOjFweCBzb2xpZCAjY2NjIiBjb2xzcGFuPSJ+dG90YWxfY29sdW1uc34iIGFsaWduPSJyaWdodCI+CiAgICAgICAgPGRpdiBjbGFzcz0iZm9ybXNlYXJjaCI+CiAgICAgICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJzZWFyY2hffnRhYmxlX2lkfiIgcGxhY2Vob2xkZXI9In5zZWFyY2hfbGFiZWx+Li4uIiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDIxMHB4OyI+IAogICAgICAgICAgICA8YSBocmVmPSJqYXZhc2NyaXB0OmFqYXhfc2VuZCgnY29yZS9zZWFyY2hfdGFibGUnLCAnfmFqYXhfZGF0YX4nLCAnc2VhcmNoX350YWJsZV9pZH4nKTsiIGNsYXNzPSJidG4gYnRuLXByaW1hcnkgYnRuLW1kIj48aSBjbGFzcz0iZmEgZmEtc2VhcmNoIj48L2k+PC9hPgogICAgICAgIDwvZGl2PgogICAgPC90ZD4KPC90cj4KCgpbW2RhdGFfdGFibGUuZGVsZXRlX2J1dHRvbl1dCjxhIGhyZWY9ImphdmFzY3JpcHQ6YWpheF9jb25maXJtKCdBcmUgeW91IHN1cmUgeW91IHdhbnQgdG8gZGVsZXRlIHRoZSBjaGVja2VkIHJlY29yZHM/JywgJ2NvcmUvZGVsZXRlX3Jvd3MnLCAnfmFqYXhfZGF0YX4nLCAnJyk7IiBjbGFzcz0iYnRuIGJ0bi1wcmltYXJ5IGJ0bi1tZCBib3RvbnRlc3QiIHN0eWxlPSJmbG9hdDogbGVmdDsiPn5kZWxldGVfYnV0dG9uX2xhYmVsfjwvYT4KCgoKKioqKioqKioqKioqKioqKioqKioKKiA8YTpwYWdpbmF0aW9uPgoqCiogUGFnaW5hdGlvbiBsaW5rcywgZ2VuZXJhbGx5IGRpc3BsYXllZCBhdCB0aGUgYm90dG9tIG9mIAoqIGRhdGEgdGFibGVzLCBidXQgY2FuIGJlIHVzZWQgYW55d2hlcmUuCioqKioqKioqKioqKioqKioqKioqCgpbW3BhZ2luYXRpb25dXQo8c3BhbiBpZD0icGduc3VtbWFyeV9+aWR+IiBzdHlsZT0idmVydGljYWwtYWxpZ246IG1pZGRsZTsgZm9udC1zaXplOiA4cHQ7IG1hcmdpbi1yaWdodDogN3B4OyI+CiAgICA8Yj5+c3RhcnRfcmVjb3JkfiAtIH5lbmRfcmVjb3JkfjwvYj4gb2YgPGI+fnRvdGFsX3JlY29yZHN+PC9iPgo8L3NwYW4+Cgo8dWwgY2xhc3M9InBhZ2luYXRpb24iIGlkID0icGduX35pZH4iPgogICAgfml0ZW1zfgo8L3VsPgoKCltbcGFnZWluYXRpb24uaXRlbV1dCjxsaSBzdHlsZT0iZGlzcGxheTogfmRpc3BsYXl+OyI+PGEgaHJlZj0ifnVybH4iPn5uYW1lfjwvYT48L2xpPgoKW1twYWdpbmF0aW9uLmFjdGl2ZV9pdGVtXV0KPGxpIGNsYXNzPSJhY3RpdmUiPjxhPn5wYWdlfjwvYT48L2xpPgoKCgoqKioqKioqKioqKioqKioqKioqKgoqIDxhOmRyb3Bkb3duX2FsZXJ0cz4KKiA8YTpkcm9wZG93bl9tZXNzYWdlcz4KKgoqIFRoZSBsaXN0IGl0ZW1zIHVzZWQgZm9yIHRoZSB0d28gZHJvcCBkb3duIGxpc3RzLCBub3RpZmljYXRpb25zIC8gYWxlcnRzIGFuZCAKKiBtZXNzYWdlcy4gIFRoZXNlIGFyZSBnZW5lcmFsbHkgZGlzcGxheWVkIGluIHRoZSB0b3AgcmlnaHQgY29ybmVyIAoqIG9mIGFkbWluIHBhbmVsIC8gbWVtYmVyIGFyZWEgdGhlbWVzLgoqKioqKioqKioqKioqKioqKioqKgoKCltbZHJvcGRvd24uYWxlcnRdXQo8bGkgY2xhc3M9Im1lZGlhIj4KICAgIDxkaXYgY2xhc3M9Im1lZGlhLWJvZHkiPgogICAgICAgIDxhIGhyZWY9In51cmx+Ij5+bWVzc2FnZX4KICAgICAgICA8ZGl2IGNsYXNzPSJ0ZXh0LW11dGVkIGZvbnQtc2l6ZS1zbSI+fnRpbWV+PC9kaXY+Cgk8L2E+CiAgICA8L2Rpdj4KPC9saT4KCgpbW2Ryb3Bkb3duLm1lc3NhZ2VdXQo8bGkgY2xhc3M9Im1lZGlhIj4KICAgIDxkaXYgY2xhc3M9Im1lZGlhLWJvZHkiPgoKICAgICAgICA8ZGl2IGNsYXNzPSJtZWRpYS10aXRsZSI+CiAgICAgICAgICAgIDxhIGhyZWY9In51cmx+Ij4KICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJmb250LXdlaWdodC1zZW1pYm9sZCI+fmZyb21+PC9zcGFuPgogICAgICAgICAgICAgICAgPHNwYW4gY2xhc3M9InRleHQtbXV0ZWQgZmxvYXQtcmlnaHQgZm9udC1zaXplLXNtIj5+dGltZX48L3NwYW4+CiAgICAgICAgICAgIDwvYT4KICAgICAgICA8L2Rpdj4KCiAgICAgICAgPHNwYW4gY2xhc3M9InRleHQtbXV0ZWQiPn5tZXNzYWdlfjwvc3Bhbj4KICAgIDwvZGl2Pgo8L2xpPgoKCgoqKioqKioqKioqKioqKioqKioqKgoqIDxhOmJveGxpc3RzPgoqCiogVGhlIGJveGxpc3RzIGFzIHNlZW4gb24gcGFnZXMgc3VjaCBhcyBTZXR0aW5ncy0+VXNlcnMuICBVc2VkIHRvIAoqIGRpc3BsYXkgbGlua3MgdG8gbXVsdGlwbGUgcGFnZXMgd2l0aCBkZXNjcmlwdGlvbnMuCioqKioqKioqKioqKioqKioqKioqCgpbW2JveGxpc3RdXQo8dWwgY2xhc3M9ImJveGxpc3QiPgogICAgfml0ZW1zfgo8L3VsPgoKCgpbW2JveGxpc3QuaXRlbV1dCjxsaT4KICAgIDxhIGhyZWY9In51cmx+Ij4KICAgICAgICA8Yj5+dGl0bGV+PC9iPjxiciAvPgogICAgICAgIH5kZXNjcmlwdGlvbn4KICAgIDwvYT4KPC9saT4KCgoqKioqKioqKioqKioqKioqKioqKgoqIGRhc2hib2FyZAoqCiogSFRNTCBzbmlwcGV0cyBmb3IgdGhlIGRhc2hib2FyZCB3aWRnZXRzLgoqKioqKioqKioqKioqKioqKioqKgoKW1tkYXNoYm9hcmRdXQoKPGRpdiBjbGFzcz0icm93IGJveGdyYWYiPgogICAgfnRvcF9pdGVtc34KPC9kaXY+Cgo8ZGl2IGNsYXNzPSJwYW5lbCBwYW5lbC1mbGF0Ij4KICAgIDxhOmZ1bmN0aW9uIGFsaWFzPSJkaXNwbGF5X3RhYmNvbnRyb2wiIHRhYmNvbnRyb2w9ImNvcmU6ZGFzaGJvYXJkIj4KPC9kaXY+Cgo8ZGl2IGNsYXNzPSJzaWRlYmFyIHNpZGViYXItbGlnaHQgYmctdHJhbnNwYXJlbnQgc2lkZWJhci1jb21wb25lbnQgc2lkZWJhci1jb21wb25lbnQtcmlnaHQgYm9yZGVyLTAgc2hhZG93LTAgb3JkZXItMSBvcmRlci1tZC0yIHNpZGViYXItZXhwYW5kLW1kIj4KICAgIDxkaXYgY2xhc3M9InNpZGViYXItY29udGVudCI+CiAgICAgICAgfnJpZ2h0X2l0ZW1zfgogICAgPC9kaXY+CjwvZGl2PgoKW1tkYXNoYm9hcmQudG9wX2l0ZW1dXQo8ZGl2IGNsYXNzPSJjb2wtbGctNCI+CiAgICA8ZGl2IGNsYXNzPSJ+cGFuZWxfY2xhc3N+Ij4KICAgICAgICA8ZGl2IGNsYXNzPSJwYW5lbC1ib2R5Ij4KCiAgICAgICAgICAgIDxoMyBjbGFzcz0ibm8tbWFyZ2luIj4zLDQ1MDwvaDM+CiAgICAgICAgICAgICAgICB+dGl0bGV+CiAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8ZGl2IGNsYXNzPSJ0ZXh0LW11dGVkIHRleHQtc2l6ZS1zbWFsbCI+fmNvbnRlbnRzfjwvZGl2PgogICAgICAgIDwvZGl2PgogICAgICAgIDxkaXYgY2xhc3M9ImNvbnRhaW5lci1mbHVpZCI+CiAgICAgICAgICAgIDxkaXYgaWQ9In5kaXZpZH4iPjwvZGl2PgogICAgICAgIDwvZGl2PgogICAgPC9kaXY+CjwvZGl2PgoKCgpbW2Rhc2hib2FyZC5yaWdodF9pdGVtXV0KPGRpdiBjbGFzcz0iY2FyZCI+CiAgICA8ZGl2IGNsYXNzPSJjYXJkLWhlYWRlciBiZy10cmFuc3BhcmVudCBoZWFkZXItZWxlbWVudHMtaW5saW5lIj4KICAgICAgICA8c3BhbiBjbGFzcz0iY2FyZC10aXRsZSBmb250LXdlaWdodC1zZW1pYm9sZCI+fnRpdGxlfjwvc3Bhbj4KICAgIDwvZGl2PgogICAgPGRpdiBjbGFzcz0iY2FyZC1ib2R5Ij4KICAgICAgICA8dWwgY2xhc3M9Im1lZGlhLWxpc3QiPgogICAgICAgICAgICA8bGkgY2xhc3M9Im1lZGlhIj4KICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9Im1lZGlhLWJvZHkiPgogICAgICAgICAgICAgICAgICAgIH5jb250ZW50c34KICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8L2xpPgogICAgICAgIDwvdWw+CiAgICA8L2Rpdj4KPC9kaXY+CgoKCgo='
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
        'name' => $name,
        'description' => ($theme->description ?? ''),
        'author_name' => ($theme->author_name ?? ''),
        'author_email' => ($theme->author_email ?? ''),
        'author_url' => ($theme->author_url ?? ''),
        'envato_item_id' => ($theme->envato_item_id ?? ''),
        'envato_username' => ($theme->envato_username ?? ''),
        'envato_url' => ($theme->envato_url ?? '')
    );

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
 */
public function install(string $theme_alias, int $repo_id = 0)
{ 

    // Debug
    debug::add(2, tr("Starting to download and install theme, {1}", $theme_alias));

    // Download
    list($repo_id, $zip_file, $vars) = $this->download($theme_alias, $repo_id);

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
    if ($vars['area'] == 'members') { 
        app::update_config_var('users:theme_members', $theme_alias);
    } else { 
        app::update_config_var('core:theme_public', $theme_alias);
    }

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
 */
protected function download(string $theme_alias, int $repo_id = 0)
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

    // Download theme
    $vars = $network->send_repo_request((int) $repo_id, $theme_alias, 'download', array('type' => 'theme'));

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

