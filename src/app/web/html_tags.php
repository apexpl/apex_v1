<?php
declare(strict_types = 1);

namespace apex\app\web;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\libc\view;
use apex\libc\date;
use apex\libc\hashes;
use apex\core\dashboard;


/**
 * Handles processing of all the special <a:...> tags 
 * that are supported by views within Apex.
 */
class html_tags
{


    // Properties
    private $tags = [];
    private $default_tags = 'YTo0NDp7czoxMDoiZm9ybV90YWJsZSI7czoxMDQ6Ijx0YWJsZSBib3JkZXI9IjAiIGNsYXNzPSJmb3JtX3RhYmxlIiBzdHlsZT0id2lkdGg6IH53aWR0aH47IGFsaWduOiB+YWxpZ25+OyI+CiAgICB+Y29udGVudHN+CjwvdGFibGU+CgoKIjtzOjE0OiJmb3JtX3RhYmxlLnJvdyI7czoxMzM6Ijx0cj4KICAgIDx0ZD48bGFiZWwgZm9yPSJ+bmFtZX4iPn5sYWJlbH46PC9sYWJlbD48L3RkPgogICAgPHRkPjxkaXYgY2xhc3M9ImZvcm0tZ3JvdXAiPgogICAgICAgIH5mb3JtX2ZpZWxkfgogICAgPC9kaXY+PC90ZD4KPC90cj4KCgoiO3M6MjA6ImZvcm1fdGFibGUuc2VwYXJhdG9yIjtzOjU1OiI8dHI+CiAgICA8dGQgY29sc3Bhbj0iMiI+PGg1Pn5sYWJlbH48L2g1PjwvdGQ+CjwvdHI+CgoKIjtzOjExOiJmb3JtLnN1Ym1pdCI7czoxNDA6IjxkaXYgY2xhc3M9InRleHQtbGVmdCI+CiAgICA8YnV0dG9uIHR5cGU9InN1Ym1pdCIgbmFtZT0ic3VibWl0IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImJ0biBidG4tcHJpbWFyeSBidG4tfnNpemV+Ij5+bGFiZWx+PC9idXR0b24+CjwvZGl2PgoKIjtzOjEwOiJmb3JtLnJlc2V0IjtzOjgzOiI8IS0tIDxidXR0b24gdHlwZT0icmVzZXQiIGNsYXNzPSJidG4gYnRuLXByaW1hcnkgYnRuLW1kIj5SZXNldCBGb3JtPC9idXR0b24+IC0tPgoKCiI7czoxMToiZm9ybS5idXR0b24iO3M6NjY6IjxhIGhyZWY9In5ocmVmfiIgY2xhc3M9ImJ0biBidG4tcHJpbmFyeSBidG4tfnNpemV+Ij5+bGFiZWx+PC9hPgoKCiI7czoxMjoiZm9ybS5ib29sZWFuIjtzOjIzMDoiPGRpdiBjbGFzcz0icmFkaW9mb3JtIj4KICAgIDxpbnB1dCB0eXBlPSJyYWRpbyIgbmFtZT0ifm5hbWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiB2YWx1ZT0iMSIgfmNoa195ZXN+IC8+IDxzcGFuPlllczwvc3Bhbj4gCiAgICA8aW5wdXQgdHlwZT0icmFkaW8iIG5hbWU9In5uYW1lfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgdmFsdWU9IjAiIH5jaGtfbm9+IC8+IDxzcGFuPk5vPC9zcGFuPiAKPC9kaXY+CgoiO3M6MTE6ImZvcm0uc2VsZWN0IjtzOjg5OiI8c2VsZWN0IG5hbWU9In5uYW1lfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgfndpZHRofiB+b25jaGFuZ2V+PgogICAgfm9wdGlvbnN+Cjwvc2VsZWN0PgoKCiI7czoxMjoiZm9ybS50ZXh0Ym94IjtzOjEyNToiPGlucHV0IHR5cGU9In50eXBlfiIgbmFtZT0ifm5hbWV+IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgaWQ9In5pZH4iIH5wbGFjZWhvbGRlcn4gfmFjdGlvbnN+IH52YWxpZGF0aW9ufiAvPgoKCgoiO3M6MTM6ImZvcm0udGV4dGFyZWEiO3M6MTEwOiI8dGV4dGFyZWEgbmFtZT0ifm5hbWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiBpZD0ifmlkfiIgc3R5bGU9IndpZHRoOiAxMDAlIiB+cGxhY2Vob2xkZXJ+Pn52YWx1ZX48L3RleHRhcmVhPgoKCiI7czo5OiJmb3JtLmRhdGUiO3M6MzQzOiI8c2VsZWN0IG5hbWU9In5uYW1lfl9tb250aCIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiAxMjBweDsgZmxvYXQ6IGxlZnQ7Ij4KICAgIH5tb250aF9vcHRpb25zfgo8L3NlbGVjdD4gCjxzZWxlY3QgbmFtZT0ifm5hbWV+X2RheX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogMzBweDsgZmxvYXQ6IGxlZnQ7Ij4KICAgIH5kYXlfb3B0aW9uc34KPC9zZWxlY3Q+LCAKPHNlbGVjdCBuYW1lPSJ+bmFtZX5feWVhcn4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogNzBweDsgZmxvYXQ6IGxlZnQ7Ij4KICAgIH55ZWFyX29wdGlvbnN+Cjwvc2VsZWN0PgoKIjtzOjE4OiJmb3JtLmRhdGVfaW50ZXJ2YWwiO3M6MzcyOiI8ZGl2IGNsYXNzPSJmb3JtLWdyb3VwIj4KICAgIDxkaXYgY2xhc3M9ImNvbC1sZy04IiBzdHlsZT0icGFkZGluZy1sZWZ0OiAwIj4KICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ifm5hbWV+X251bSIgY2xhc3M9ImZvcm0tY29udHJvbCIgdmFsdWU9In5udW1+IiA+IAogICAgPC9kaXY+CiAgICA8ZGl2IGNsYXNzPSJjb2wtbGctNCIgc3R5bGU9InBhZGRpbmctcmlnaHQ6IDAiPgogICAgICAgIDxzZWxlY3QgbmFtZT0ifm5hbWV+X3BlcmlvZCIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiAxMDAlIiA+CiAgICAgICAgICAgIH5wZXJpb2Rfb3B0aW9uc34KICAgICAgICA8L3NlbGVjdD4KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKCgoiO3M6MzoiYm94IjtzOjE1MjoiPGRpdiBjbGFzcz0icGFuZWwgcGFuZWwtZGVmYXVsdCI+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1oZWFkaW5nIj4gfmJveF9oZWFkZXJ+PC9kaXY+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1ib2R5Ij4KICAgICAgICB+Y29udGVudHN+CiAgICA8L2Rpdj4KPC9kaXY+CgoiO3M6MTA6ImJveC5oZWFkZXIiO3M6MTE2OiI8c3BhbiBzdHlsZT0iYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkICMzMzMzMzM7IG1hcmdpbi1ib3R0b206IDhweDsiPgogICAgPGgzPn50aXRsZX48L2gzPgogICAgfmNvbnRlbnRzfgo8L3NwYW4+CgoKCiI7czo4OiJjYWxsb3V0cyI7czoxMjU6IjxkaXYgY2xhc3M9ImNhbGxvdXQgY2FsbG91dC1+Y3NzX2FsaWFzfiB0ZXh0LWNlbnRlciBzdWNjZXNzIj48cD4KICAgIDxpIGNsYXNzPSJpY29uIH5pY29ufiI+PC9pPgogICAgfm1lc3NhZ2Vzfgo8L3A+PC9kaXY+CgoKIjtzOjEyOiJjYWxsb3V0cy5jc3MiO3M6MTAzOiJbCiAgICAic3VjY2VzcyI6ICJzdWNjZXNzIiwgCiAgICAiZXJyb3IiOiAiZGFuZ2VyIiwgCiAgICAid2FybmluZyI6ICJ3YXJuaW5nIiwgCiAgICAiaW5mbyI6ICJpbmZvIgpdCgoKIjtzOjEzOiJjYWxsb3V0cy5pY29uIjtzOjEyMzoiWwogICAgInN1Y2Nlc3MiOiAiZmEgZmEtY2hlY2siLCAKICAgICJlcnJvciI6ICJmYSBmYS1iYW4iLCAKICAgICJ3YXJuaW5nIjogImZhIGZhLXdhcm5pbmciLCAKICAgICJpbmZvIjogImZhIGZhLWluZm8iCl0KCgoKIjtzOjEwOiJuYXYuaGVhZGVyIjtzOjE0NToiPGxpIGNsYXNzPSJuYXYtaXRlbS1oZWFkZXIiPjxkaXYgY2xhc3M9InRleHQtdXBwZXJjYXNlIGZvbnQtc2l6ZS14cyBsaW5lLWhlaWdodC14cyI+fm5hbWV+PC9kaXY+IDxpIGNsYXNzPSJpY29uLW1lbnUiIHRpdGxlPSJ+bmFtZX4iPjwvaT48L2xpPgoKCiI7czoxMDoibmF2LnBhcmVudCI7czoyMDc6IjxsaSBjbGFzcz0ibmF2LWl0ZW0gbmF2LWl0ZW0tc3VibWVudSI+CiAgICA8YSBocmVmPSJ+dXJsfiIgY2xhc3M9Im5hdi1saW5rIj5+aWNvbn4gPHNwYW4+fm5hbWV+PC9zcGFuPjwvYT4KICAgIDx1bCBjbGFzcz0ibmF2IG5hdi1ncm91cC1zdWIiIGRhdGEtc3VibWVudS10aXRsZT0ifm5hbWV+Ij4KICAgICAgICB+c3VibWVudXN+CiAgICA8L3VsPgo8L2xpPgoKCiI7czo4OiJuYXYubWVudSI7czo3OToiPGxpIGNsYXNzPSJuYXYtaXRlbSI+PGEgaHJlZj0ifnVybH4iIGNsYXNzPSJuYXYtbGluayI+fmljb25+fm5hbWV+PC9hPjwvbGk+CgoKCiI7czoxMToidGFiX2NvbnRyb2wiO3M6MTYzOiIKPGRpdiBjbGFzcz0ibmF2LXRhYnMtY3VzdG9tIj4KICAgIDx1bCBjbGFzcz0ibmF2IG5hdi10YWJzIj4KICAgICAgICB+bmF2X2l0ZW1zfgogICAgPC91bD4KCiAgICA8ZGl2IGNsYXNzPSJ0YWItY29udGVudCI+CiAgICAgICAgfnRhYl9wYWdlc34KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKIjtzOjIwOiJ0YWJfY29udHJvbC5uYXZfaXRlbSI7czo4MToiPGxpIGNsYXNzPSJ+YWN0aXZlfiI+PGEgaHJlZj0iI3RhYn50YWJfbnVtfiIgZGF0YS10b2dnbGU9InRhYiI+fm5hbWV+PC9hPjwvbGk+CgoKIjtzOjE2OiJ0YWJfY29udHJvbC5wYWdlIjtzOjc0OiI8ZGl2IGNsYXNzPSJ0YWItcGFuZSB+YWN0aXZlfiIgaWQ9InRhYn50YWJfbnVtfiI+CiAgICB+Y29udGVudHN+CjwvZGl2PgoKCiI7czoyMjoidGFiX2NvbnRyb2wuY3NzX2FjdGl2ZSI7czoxMToiYWN0aXZlCgoKCgoiO3M6MTA6ImRhdGFfdGFibGUiO3M6NDIwOiI8dGFibGUgY2xhc3M9InRhYmxlIHRhYmxlLWJvcmRlcmVkIHRhYmxlLXN0cmlwZWQgdGFibGUtaG92ZXIiIGlkPSJ+dGFibGVfaWR+Ij4KICAgIDx0aGVhZD4KICAgICAgICB+c2VhcmNoX2Jhcn4KICAgIDx0cj4KICAgICAgICB+aGVhZGVyX2NlbGxzfgogICAgPC90cj4KICAgIDwvdGhlYWQ+CgogICAgPHRib2R5IGlkPSJ+dGFibGVfaWR+X3Rib2R5IiBjbGFzcz0iYm9keXRhYmxlIj4KICAgICAgICB+dGFibGVfYm9keX4KICAgIDwvdGJvZHk+CgogICAgPHRmb290Pjx0cj4KICAgICAgICA8dGQgY29sc3Bhbj0ifnRvdGFsX2NvbHVtbnN+IiBhbGlnbj0icmlnaHQiPgogICAgICAgICAgICB+ZGVsZXRlX2J1dHRvbn4KICAgICAgICAgICAgfnBhZ2luYXRpb25+CiAgICAgICAgPC90ZD4KICAgIDwvdHI+PC90Zm9vdD4KPC90YWJsZT4KCgoiO3M6MTM6ImRhdGFfdGFibGUudGgiO3M6NzM6Ijx0aCBjbGFzcz0iYm94aGVhZGVyIj4gPHNwYW4+fm5hbWV+PC9zcGFuPiB+c29ydF9kZXNjfiB+c29ydF9hc2N+PC90aD4KCgoiO3M6MTk6ImRhdGFfdGFibGUuc29ydF9hc2MiO3M6MjA5OiI8YSBocmVmPSJqYXZhc2NyaXB0OmFqYXhfc2VuZCgnY29yZS9zb3J0X3RhYmxlJywgJ35hamF4X2RhdGF+JnNvcnRfY29sPX5jb2xfYWxpYXN+JnNvcnRfZGlyPWFzYycsICdub25lJyk7IiBib3JkZXI9IjAiIHRpdGxlPSJTb3J0IEFzY2VuZGluZyB+Y29sX2FsaWFzfiIgY2xhc3M9ImFzYyI+CiAgICA8aSBjbGFzcz0iZmEgZmEtc29ydC1hc2MiPjwvaT4KPC9hPgoKCiI7czoyMDoiZGF0YV90YWJsZS5zb3J0X2Rlc2MiO3M6MjEyOiI8YSBocmVmPSJqYXZhc2NyaXB0OmFqYXhfc2VuZCgnY29yZS9zb3J0X3RhYmxlJywgJ35hamF4X2RhdGF+JnNvcnRfY29sPX5jb2xfYWxpYXN+JnNvcnRfZGlyPWRlc2MnLCAnbm9uZScpOyIgYm9yZGVyPSIwIiB0aXRsZT0iU29ydCBEZWNlbmRpbmcgfmNvbF9hbGlhc34iIGNsYXNzPSJkZXNjIj4KICAgIDxpIGNsYXNzPSJmYSBmYS1zb3J0LWRlc2MiPjwvaT4KPC9hPgoKCiI7czoyMToiZGF0YV90YWJsZS5zZWFyY2hfYmFyIjtzOjQ1MjoiPHRyPgogICAgPHRkIHN0eWxlPSJib3JkZXItdG9wOjFweCBzb2xpZCAjY2NjIiBjb2xzcGFuPSJ+dG90YWxfY29sdW1uc34iIGFsaWduPSJyaWdodCI+CiAgICAgICAgPGRpdiBjbGFzcz0iZm9ybXNlYXJjaCI+CiAgICAgICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJzZWFyY2hffnRhYmxlX2lkfiIgcGxhY2Vob2xkZXI9In5zZWFyY2hfbGFiZWx+Li4uIiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDIxMHB4OyI+IAogICAgICAgICAgICA8YSBocmVmPSJqYXZhc2NyaXB0OmFqYXhfc2VuZCgnY29yZS9zZWFyY2hfdGFibGUnLCAnfmFqYXhfZGF0YX4nLCAnc2VhcmNoX350YWJsZV9pZH4nKTsiIGNsYXNzPSJidG4gYnRuLXByaW1hcnkgYnRuLW1kIj48aSBjbGFzcz0iZmEgZmEtc2VhcmNoIj48L2k+PC9hPgogICAgICAgIDwvZGl2PgogICAgPC90ZD4KPC90cj4KCgoiO3M6MjQ6ImRhdGFfdGFibGUuZGVsZXRlX2J1dHRvbiI7czoyMjI6IjxhIGhyZWY9ImphdmFzY3JpcHQ6YWpheF9jb25maXJtKCdBcmUgeW91IHN1cmUgeW91IHdhbnQgdG8gZGVsZXRlIHRoZSBjaGVja2VkIHJlY29yZHM/JywgJ2NvcmUvZGVsZXRlX3Jvd3MnLCAnfmFqYXhfZGF0YX4nLCAnJyk7IiBjbGFzcz0iYnRuIGJ0bi1wcmltYXJ5IGJ0bi1tZCBib3RvbnRlc3QiIHN0eWxlPSJmbG9hdDogbGVmdDsiPn5kZWxldGVfYnV0dG9uX2xhYmVsfjwvYT4KCgoKCiI7czoxMDoicGFnaW5hdGlvbiI7czoyMzA6IjxzcGFuIGlkPSJwZ25zdW1tYXJ5X35pZH4iIHN0eWxlPSJ2ZXJ0aWNhbC1hbGlnbjogbWlkZGxlOyBmb250LXNpemU6IDhwdDsgbWFyZ2luLXJpZ2h0OiA3cHg7Ij4KICAgIDxiPn5zdGFydF9yZWNvcmR+IC0gfmVuZF9yZWNvcmR+PC9iPiBvZiA8Yj5+dG90YWxfcmVjb3Jkc348L2I+Cjwvc3Bhbj4KCjx1bCBjbGFzcz0icGFnaW5hdGlvbiIgaWQgPSJwZ25ffmlkfiI+CiAgICB+aXRlbXN+CjwvdWw+CgoKIjtzOjE2OiJwYWdlaW5hdGlvbi5pdGVtIjtzOjY1OiI8bGkgc3R5bGU9ImRpc3BsYXk6IH5kaXNwbGF5fjsiPjxhIGhyZWY9In51cmx+Ij5+bmFtZX48L2E+PC9saT4KCiI7czoyMjoicGFnaW5hdGlvbi5hY3RpdmVfaXRlbSI7czo0MzoiPGxpIGNsYXNzPSJhY3RpdmUiPjxhPn5wYWdlfjwvYT48L2xpPgoKCgoKCiI7czoxNDoiZHJvcGRvd24uYWxlcnQiO3M6MTY1OiI8bGkgY2xhc3M9Im1lZGlhIj4KICAgIDxkaXYgY2xhc3M9Im1lZGlhLWJvZHkiPgogICAgICAgIDxhIGhyZWY9In51cmx+Ij5+bWVzc2FnZX4KICAgICAgICA8ZGl2IGNsYXNzPSJ0ZXh0LW11dGVkIGZvbnQtc2l6ZS1zbSI+fnRpbWV+PC9kaXY+Cgk8L2E+CiAgICA8L2Rpdj4KPC9saT4KCgoiO3M6MTY6ImRyb3Bkb3duLm1lc3NhZ2UiO3M6MzYxOiI8bGkgY2xhc3M9Im1lZGlhIj4KICAgIDxkaXYgY2xhc3M9Im1lZGlhLWJvZHkiPgoKICAgICAgICA8ZGl2IGNsYXNzPSJtZWRpYS10aXRsZSI+CiAgICAgICAgICAgIDxhIGhyZWY9In51cmx+Ij4KICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJmb250LXdlaWdodC1zZW1pYm9sZCI+fmZyb21+PC9zcGFuPgogICAgICAgICAgICAgICAgPHNwYW4gY2xhc3M9InRleHQtbXV0ZWQgZmxvYXQtcmlnaHQgZm9udC1zaXplLXNtIj5+dGltZX48L3NwYW4+CiAgICAgICAgICAgIDwvYT4KICAgICAgICA8L2Rpdj4KCiAgICAgICAgPHNwYW4gY2xhc3M9InRleHQtbXV0ZWQiPn5tZXNzYWdlfjwvc3Bhbj4KICAgIDwvZGl2Pgo8L2xpPgoKCgoKIjtzOjc6ImJveGxpc3QiO3M6NDI6Ijx1bCBjbGFzcz0iYm94bGlzdCI+CiAgICB+aXRlbXN+CjwvdWw+CgoKCiI7czoxMDoiZm9ybS5waG9uZSI7czoyNDQ6IjxkaXYgY2xhc3M9ImZvcm0tZ3JvdXAiPgogICAgPHNlbGVjdCBuYW1lPSJ+bmFtZX5fY291bnRyeSIgY2xhc3M9ImZvcm0tY29udHJvbCBjb2wtbGctMiI+CiAgICAgICAgfmNvdW50cnlfY29kZV9vcHRpb25zfgogICAgPC9zZWxlY3Q+IAogICAgPGlucHV0IHR5cGU9InRleHQiIG5hbWU9In5uYW1lfiIgdmFsdWU9In52YWx1ZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wgY29sLWxnLTEwIiAgfnBsYWNlaG9sZGVyfj4KPC9kaXY+CgoiO3M6MTE6ImZvcm0uYW1vdW50IjtzOjIwMDoiPHNwYW4gc3R5bGU9ImZsb2F0OiBsZWZ0OyI+fmN1cnJlbmN5X3NpZ25+PC9zcGFuPiAKPGlucHV0IHR5cGU9InRleHQiIG5hbWU9In5uYW1lfiIgdmFsdWU9In52YWx1ZX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogNjBweDsgZmxvYXQ6IGxlZnQ7IiB+cGxhY2Vob2xkZXJ+IGRhdGEtcGFyc2xleS10eXBlPSJkZWNpbWFsIj4KCgoiO3M6OToiZm9ybS50aW1lIjtzOjIzMDoiPHNlbGVjdCBuYW1lPSJ+bmFtZX5faG91ciIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA2MHB4OyBmbG9hdDogbGVmdDsiPgogICAgfmhvdXJfb3B0aW9uc34KPC9zZWxlY3Q+IDogCjxzZWxlY3QgbmFtZT0ifm5hbWV+X21pbiIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA2MHB4OyBmbG9hdDogbGVmdDsiPgogICAgfm1pbnV0ZV9vcHRpb25zfgo8L3NlbGVjdD4KCgoiO3M6OToiaW5wdXRfYm94IjtzOjExNzoiPGRpdiBjbGFzcz0icGFuZWwgcGFuZWwtZGVmYXVsdCBzZWFyY2hfdXNlciI+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1ib2R5Ij4KICAgICAgICB+Y29udGVudHN+CiAgICA8L2Rpdj4KPC9kaXY+CgoKCgoKIjtzOjEyOiJib3hsaXN0Lml0ZW0iO3M6OTU6IjxsaT4KICAgIDxhIGhyZWY9In51cmx+Ij4KICAgICAgICA8Yj5+dGl0bGV+PC9iPjxiciAvPgogICAgICAgIH5kZXNjcmlwdGlvbn4KICAgIDwvYT4KPC9saT4KCgoKIjtzOjk6ImRhc2hib2FyZCI7czozODU6Igo8ZGl2IGNsYXNzPSJyb3cgYm94Z3JhZiI+CiAgICB+dG9wX2l0ZW1zfgo8L2Rpdj4KCjxkaXYgY2xhc3M9InBhbmVsIHBhbmVsLWZsYXQiPgogICAgPGE6ZnVuY3Rpb24gYWxpYXM9ImRpc3BsYXlfdGFiY29udHJvbCIgdGFiY29udHJvbD0iY29yZTpkYXNoYm9hcmQiPgo8L2Rpdj4KCjxkaXYgY2xhc3M9InNpZGViYXIgc2lkZWJhci1saWdodCBiZy10cmFuc3BhcmVudCBzaWRlYmFyLWNvbXBvbmVudCBzaWRlYmFyLWNvbXBvbmVudC1yaWdodCBib3JkZXItMCBzaGFkb3ctMCBvcmRlci0xIG9yZGVyLW1kLTIgc2lkZWJhci1leHBhbmQtbWQiPgogICAgPGRpdiBjbGFzcz0ic2lkZWJhci1jb250ZW50Ij4KICAgICAgICB+cmlnaHRfaXRlbXN+CiAgICA8L2Rpdj4KPC9kaXY+CgoiO3M6MTg6ImRhc2hib2FyZC50b3BfaXRlbSI7czozNzI6IjxkaXYgY2xhc3M9ImNvbC1sZy00Ij4KICAgIDxkaXYgY2xhc3M9In5wYW5lbF9jbGFzc34iPgogICAgICAgIDxkaXYgY2xhc3M9InBhbmVsLWJvZHkiPgoKICAgICAgICAgICAgPGgzIGNsYXNzPSJuby1tYXJnaW4iPjMsNDUwPC9oMz4KICAgICAgICAgICAgICAgIH50aXRsZX4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgIDxkaXYgY2xhc3M9InRleHQtbXV0ZWQgdGV4dC1zaXplLXNtYWxsIj5+Y29udGVudHN+PC9kaXY+CiAgICAgICAgPC9kaXY+CiAgICAgICAgPGRpdiBjbGFzcz0iY29udGFpbmVyLWZsdWlkIj4KICAgICAgICAgICAgPGRpdiBpZD0ifmRpdmlkfiI+PC9kaXY+CiAgICAgICAgPC9kaXY+CiAgICA8L2Rpdj4KPC9kaXY+CgoKCiI7czoyMDoiZGFzaGJvYXJkLnJpZ2h0X2l0ZW0iO3M6NDA3OiI8ZGl2IGNsYXNzPSJjYXJkIj4KICAgIDxkaXYgY2xhc3M9ImNhcmQtaGVhZGVyIGJnLXRyYW5zcGFyZW50IGhlYWRlci1lbGVtZW50cy1pbmxpbmUiPgogICAgICAgIDxzcGFuIGNsYXNzPSJjYXJkLXRpdGxlIGZvbnQtd2VpZ2h0LXNlbWlib2xkIj5+dGl0bGV+PC9zcGFuPgogICAgPC9kaXY+CiAgICA8ZGl2IGNsYXNzPSJjYXJkLWJvZHkiPgogICAgICAgIDx1bCBjbGFzcz0ibWVkaWEtbGlzdCI+CiAgICAgICAgICAgIDxsaSBjbGFzcz0ibWVkaWEiPgogICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0ibWVkaWEtYm9keSI+CiAgICAgICAgICAgICAgICAgICAgfmNvbnRlbnRzfgogICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgIDwvbGk+CiAgICAgICAgPC91bD4KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKCiI7fQ==';


/**
 * Construct 
 *
 * @param date $date the /app/utils/data.php class.  Injected.
 * @param hashes $hashes The /app/utils/hashes.php class.  Injected
 */
public function __construct(date $date, hashes $hashes)
{ 
    $this->theme_dir = SITE_PATH . '/views/themes/' . app::get_theme();

    // Read theme tags
    $this->read_tags();

}

/**
 * Read the /tags.tpl file for the theme.
 */
private function read_tags()
{

    // Initialize
    $this->tags = unserialize(base64_decode($this->default_tags));
    $this->theme_dir = SITE_PATH . '/views/themes/' . app::get_theme();
    if (!file_exists($this->theme_dir . '/tags.tpl')) { return; }

    // Set variables
    $lines = file($this->theme_dir . '/tags.tpl');
    $tag_name = '';
    $tag_html = '';

    // Go through lines
    foreach ($lines as $line) { 
        if (preg_match("/\*/", $line)) { continue; }

        // Check for new tag
        if (preg_match("/^\[\[(.+?)\]\]/", $line, $match)) { 
            if ($tag_html != '') { $this->tags[$tag_name] = $tag_html; }
            $tag_name = $match[1];
            $tag_html = '';
        } elseif ($tag_name != '') { 
            $tag_html .= $line;
        }
    }
    if ($tag_html != '') { $this->tags[$tag_name] = $tag_html; }

}

/**
 * Get a single tag HTML
 *
 * @param string $tag_name The tag name to get.
 *
 * @return string The HTML code of the tag.
 */
public function get_tag(string $tag_name):string
{
    $html = $this->tags[$tag_name] ?? '';
    return $html;
}

/**
 * Callouts 
 *
 * Is replaced with the standard success / error / warning messages on the top 
 * of a page contents alertying the user of a successful action being 
 * completed, user submission error, etc. 
 *
 * @param array $messages An array of the callouts to format.
 */
public function callouts(array $messages):string
{ 

    // Initialize
    $callouts = '';
    $msg_types = array('success','error','warning','info');
    $css_aliases = json_decode($this->tags['callouts.css'], true);
    $icons = json_decode($this->tags['callouts.icon'], true);

    foreach ($msg_types as $type) { 
        if (!isset($messages[$type])) { continue; }

        // Get messages
        $tmp_messages = '';
        foreach ($messages[$type] as $msg) { 
            if ($msg == '') { continue; }
            $tmp_messages .= "$msg<br />";
        }
        if ($tmp_messages == '') { continue; }

        // Get HTML
        $tmp_html = $this->tags['callouts'];
        $tmp_html = str_replace("~css_alias~", ($css_aliases[$type] ?? 'success'), $tmp_html);
        $tmp_html = str_replace("~icon~", ($icons[$type] ?? ''), $tmp_html);
        $tmp_html = str_replace("~messages~", $tmp_messages, $tmp_html);
        $callouts .= $tmp_html;
    }

    // Return
        return $callouts;

}

/**
 * Page title 
 *
 * Page title.  Checks the database first for a defined title, if none exists, 
 * checks the TPL code for <h1> tags, and otherwise just uses the site name 
 * configuration variable. 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function page_title(array $attr, string $text):string
{ 

    // Check if text only
    if (isset($attr['textonly']) && $attr['textonly'] == 1) { 
        return $text; 
    }

    // Format
    return '<h1>' . $text . '</h1>';

}

/**
 * social_links
 *
 * Returns a list of all social media links with FontAwesome icons 
 * depending on what social media profiles administrator has defined.
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function social_links(array $attr, string $text):string
{

    // Set supported social networks
    $social_networks = array(
        'facebook', 
        'twitter', 
        'youtube', 
        'linkedin', 
        'instagram', 
        'github', 
        'reddit', 
        'dribble'
    );

    // Go through social networks
    $html = '';
    foreach ($social_networks as $alias) { 
        $url = app::_config('core:site_' . $alias) ?? '';
        if ($url == '') { continue; }

        // Add to html
        $html .= "<a href=\"$url\" target=\"_blank\"><span class=\"fa fa-" . $alias . "\"></span></a> ";
    }

    // Return
    return $html;

}

/**
 * form
 *
 * Replaced with a standard <form> tag, and unless attributes are defined to 
 * the contrary, the action points to the current template being displayed, 
 * with a method of POST. 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function form($attr, $text = '')
{ 

    // Set variables
    $action = $attr['action'] ?? app::get_uri();
    $action = '/' . trim($action, '/');
    $method = $attr['method'] ?? 'POST';
    $enctype = $attr['enctype'] ?? 'application/x-www-form';
    $class = $attr['class'] ?? 'form-inline';
    $id = $attr['id'] ?? 'frm_main';
    if (isset($attr['file_upload']) && $attr['file_upload'] == 1) { $enctype = 'multipart/form-data'; }

    // Set HTML
    $html = "<form action=\"$action\" method=\"$method\" enctype=\"$enctype\" class=\"$class\" id=\"$id\" data-parsley-validate=\"\">";
    return $html;

}

/**
 * Form table.
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function form_table(array $attr, string $text):string
{ 

    // Set variables
    $width = $data['width'] ?? '95%';
    $align = $attr['align'] ?? 'left';

    // Get html
    $html = $this->tags['form_table'];
    $html = str_replace("~width~", $width, $html);
    $html = str_replace("~align~", $align, $html);
    $html = str_replace("~contents~", $text, $html);

    // Return
    return $html;

}

/**
 * Seperator.  Used to separate different groups of form fields. 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function ft_seperator(array $attr, string $text = ''):string
{ 

    $html = str_replace("~label~", tr($attr['label']), $this->tags['form_table.separator']);
    return $html;

}

/**
 * Form table row.
 *
 * @param string $form_field The tag name of the form field.
 * @param array $attr The attributes passed to the <a:field> tab.
 * @param string Any text between open and close tags.
 *
 * @return string The resulting HTML code
 */
private function ft_row(string $form_field, array $attr, string $text = '')
{

    // Initialize
    if (!isset($attr['name'])) { 
        return "<B>ERROR:</b> No 'name' attribute found in the '$form_field' field.\n";
    }
    $label = $attr['label'] ?? ucwords(str_replace("_", " ", $attr['name']));

    // Get HTML
    $html = $this->tags['form_table.row'];
    $html = str_replace("~name~", $attr['name'], $html);
    $html = str_replace("~label~", tr($label), $html);
    $html = str_replace("~form_field~", $this->$form_field($attr, $text), $html);

    // Return
    return $html;

}

/**
 * Call HTML tag.
 *
 * @param string $tag_name The method / tag name being called.
 * @param array $params The additional arams passed
 * 
 * @return string The resulting HTML code
 */
public function __call(string $tag_name, $params)
{

    // Check for ft_TAGNAME field
    if (preg_match("/^ft_(.+)/", $tag_name, $match)) { 
        $attr = $params[0] ?? array();
        $text = $params[1] ?? '';
        $html = $this->ft_row($match[1], $attr, $text);
    } else { 
        $html = "<b>ERROR:</b> TESTING The special HTML tag '$tag_name' is not supported.";
    }

    // Return
    return $html;

}


/**
 * ft_custom
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function ft_custom(array $attr, string $text = ''):string
{ 

    // Perform checks
    if ((!isset($attr['name'])) && (!isset($attr['label']))) { return "<b>ERROR:</b> No 'name' or 'label' attribute was defined with the e:ft_custom tab."; }

    // Set variables
    $label = $attr['label'] ?? ucwords(str_replace("_", " ", $attr['name']));
    $name = $attr['name'] ?? strtolower(str_replace(" ", "_", $label));
    if (isset($attr['contents'])) { $text = $attr['contents']; }
    $label = tr($label);

    // Set HTML
    $html = $this->tags['form_table.row'];
    $html = str_replace("~name~", $name, $html);
    $html = str_replace("~label~", $label, $html);
    $html = str_replace("~form_field~", $text, $html);

    // Return
    return $html;

}

/**
 * ft_blank
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function ft_blank(array $attr, string $text = ''):string
{ 

    // Set html
    $contents = $attr['contents'] ?? $text;
    $html = "<tr><td colspan=\"2\">$contents</td></tr>";

    // Return
    return $html;

}

/**
 * ft_submit
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function ft_submit(array $attr, string $text = ''):string
{ 

    // Set variables
    $has_reset = $attr['has_reset'] ?? 0;
    $align = $attr['align'] ?? 'center';

// Set HTML
$html = "<tr>\n\t<td colspan=\"2\" align=\"$align\">";
    $html .= $this->submit($attr, $text);
    if ($has_reset == 1) { 
        $html .= $this->tags['form.reset'];
    }
    $html .= "</td>\n</tr>";

    // Return
    return $html;

}

/**
 * submit
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function submit(array $attr, string $text = ''):string
{ 

    // Set variables
    $name = $attr['name'] ?? 'submit';
    $value = $attr['value'] ?? 'submit';
    $label = $attr['label'] ?? 'Submit Form';
    $size = $attr['size'] ?? 'lg';

    // Get HTML
    $html = $this->tags['form.submit'];
    $html = str_replace("~name~", $name, $html);
    $html = str_replace("~value~", $value, $html);
    $html = str_replace("~size~", $size, $html);
    $html = str_replace("~label~", tr($label), $html);

    // Return
    return $html;

}

/**
 * boolean
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function boolean(array $attr, string $text = ''):string
{ 

    // Perform checks
    if (!isset($attr['name'])) { return "The 'ft_boolean' tag does not contain a 'name' attribute."; }

    // Set variables
    $value = $attr['value'] ?? 0;
    $chk_yes = $value == 1 ? 'checked="checked"' : '';
    $chk_no = $value == 0 ? 'checked="checked"' : '';

    // Get HTML
    $html = $this->tags['form.boolean'];
    $html = str_replace("~name~", $attr['name'], $html);
    $html = str_replace("~chk_yes~", $chk_yes, $html);
    $html = str_replace("~chk_no~", $chk_no, $html);

    // Return
    return $html;

}

/**
 * select 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function select(array $attr, string $text = ''):string
{ 

    // Checks
    if (!isset($attr['name'])) { return "<b>ERROR:</b> No 'name' attribute exists within the 'select' tag."; }

    // Set variables
    $value = $attr['value'] ?? '';
    $required = $attr['required'] ?? 0;

    // Check for width / onchange
    $width = $attr['width'] ?? '';
    $onchange = $attr['onchange'] ?? '';
    if ($width != '') { $width = "style-\"width: " . $width . ";\""; }
    if ($onchange != '') { $onchange = "onchange=\"$onchange\""; }

    // Get select options
    $options = $required == 1 ? '' : '<option value="">------------</option>';
    if (isset($attr['data_source'])) { 
        $options .= hashes::parse_data_source($attr['data_source'], $value, 'select');
    } else { 
        $options .= $text;
    }

    // Get HTML
    $html = $this->tags['form.select'];
    $html = str_replace("~name~", $attr['name'], $html);
    $html = str_replace("~width~", $width, $html);
    $html = str_replace("~onchange~", $onchange, $html);
    $html = str_replace("~options~", $options, $html);

    // Return
    return $html;

}

/**
 * textbox
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function textbox(array $attr, string $text = ''):string
{ 

// Perform checks
    if (!isset($attr['name'])) { return "<v>ERROR:</b>:  No 'name' attribute within the textbox field."; }

    // Set variables
    $merge_vars = array(
        'name' => $attr['name'], 
        'type' => $attr['type'] ?? 'text',
        'value' => $attr['value'] ?? '',  
        'id' => $attr['id'] ?? 'input_' . $attr['name'], 
        'placeholder' => $attr['placeholder'] ?? '', 
        'width' => $attr['width'] ?? '', 
        'actions' => '', 
        'validation' => ''
    );
    if ($merge_vars['placeholder'] != '') { $merge_vars['placeholder'] = "placeholder=\"" . tr($merge_vars['placeholder']) . "\""; }
    if ($merge_vars['width'] != '') { $merge_vars['width'] = "style=\"width: " . $merge_vars['width'] . ";\""; }

    // Get actions
    foreach (array('onfocus','onblur','onkeyup') as $action) { 
        if (!isset($attr[$action])) { continue; }
        $merge_vars['actions'] .= $action . "=\"$attr[$action]\" ";
    }

    // Validation variables
    $required = $attr['required'] ?? 0;
    $datatype = $attr['datatype'] ?? '';
    $minlength = $attr['minlength'] ?? 0;
    $maxlength = $attr['maxlength'] ?? 0;
    $range = $attr['range'] ?? '';
    $equalto = $attr['equalto'] ?? '';

    // Get validation attributes
    $validation = '';
    if ($required == 1) { $validation .= " data-parsley-required=\"true\""; }
    if ($datatype != '') { $validation .= " data-parsley-type=\"$datatype\""; }
    if ($minlength > 0) { $validation .= " data-parsley-minlength=\"$minlength\""; }
    if ($maxlength > 0) { $validation .= " data-parsley-maxlength=\"$maxlength\""; }
    if ($range != '') { $validation .= " data-parsley-range=\"$range\""; }
    if ($equalto != '') { $validation .= " data-parsley-equalto=\"$equalto\""; }
    $merge_vars['validation'] = $validation;

    // Get HTML
    $html = $this->tags['form.textbox'];
    foreach ($merge_vars as $key => $value) { 
        $html = str_replace("~$key~", $value, $html);
    }

    // Return
    return $html;

}

/**
 * Amount text box 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function amount(array $attr, string $text = ''):string
{ 

    // Check for name
    if (!isset($attr['name'])) { 
        return "<b>ERROR:</b> There is no 'name' attribute within the 'amount' textbox"; 
    }

    // Get currency
    $currency = $attr['currency'] ?? app::_config('transaction:base_currency');
    $curdata = app::get_currency_data($currency);

    // Set variables
    $value = $attr['value'] ?? '';
    $placeholder = $attr['placeholder'] ?? '';
    if ($placeholder != '') { $placeholder = "placeholder=\"" . tr($plreaceholder) . "\""; }

    // Get HTML
    $html = $this->tags['form.amount'];
    $html = str_replace("~currency_sign~", $curdata['symbol'], $html);
    $html = str_replace("~name~", $attr['name'], $html);
    $html = str_replace("~value~", $value, $html);
    $html = str_replace("~placeholder~", $placeholder, $html);

    // Return
    return $html;

}

/**
 * phone 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function phone(array $attr, string $text = ''):string
{ 

    // Perform checks
    if (!isset($attr['name'])) { 
        return "<b>ERROR:</b> The 'phone' tag does not have a 'name' attribute."; 
    }

    // Check value
    $value = $attr['value'] ?? '';
    if (preg_match("/\+(\d+?)\s(\d+)$/", $value, $match)) { 
        $country = $match[1];
        $phone = $match[2];
    } else { $country = ''; $phone = ''; }

    // Get all country calling codes
    $codes = array();
    $rows = redis::hgetall('std:country');
    foreach ($rows as $abbr => $line) { 
        $vars = explode('::', $line);
        if (in_array($vars[4], $codes)) { continue; }
        $codes[] = $vars[4];
    }
    asort($codes);

    // Create country options
    $country_options = '';
    if ($country == '') { $country = 1; }
    foreach ($codes as $code) { 
        $chk = $code == $country ? 'selected="selected"' : '';
        $country_options .= "<option value=\"$code\" $chk>+ $code</option>";
    }

    // Get placeholdder
    $placeholder = $attr['placeholder'] ?? '';
    if ($placeholder != '') { $placeholder = "placeholder=\"" . tr($placeholder) . "\""; }

    // Get HTML
    $html = $this->tags['form.phone'];
    $html = str_replace("~name~", $attr['name'], $html);
    $html = str_replace("~country_code_options~", $country_options, $html);
    $html = str_replace("~value~", $phone, $html);
    $html = str_replace("~placeholder~", $placeholder, $html);


    // Return
    return $html;

}

/**
 * textarea
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function textarea(array $attr, string $text = ''):string
{ 

    // Perform checks
    if (!isset($attr['name'])) { return "<v>ERROR:</b>:  No 'name' attribute within the ft_textarea field."; }

    // Set variables
    $merge_vars = array(
        'name' => $attr['name'], 
        'placeholder' => $attr['placeholder'] ?? '', 
        'value' => $attr['value'] ?? $text, 
        'id' => $attr['id'] ?? 'input_' . $attr['name'],
        'placeholder' => $attr['placeholder'] ?? '',  
        'width' => $attr['width'] ?? '400px', 
        'height' => $data['height'] ?? '100px'
    );
    if ($merge_vars['placeholder'] != '') { 
        $merge_vars['placeholder'] = "placeholder=\"" . tr($merge_vars['placeholder']) . "\""; 
    }

    // Get size
    if (isset($attr['size']) && preg_match("/^(.+?),(.+)/", $attr['size'], $match)) { 
        $merge_vars['width'] = $match[1];
        $merge_vars['height'] = $match[2];
    }

    // Get HTML
    $html = $this->tags['form.textarea'];
    foreach ($merge_vars as $key => $value) { 
        $html = str_replace("~$key~", $value, $html);
    }

    // Return
    return $html;

}

/**
 * button 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function button(array $attr, string $text = ''):string
{ 

    // Set variables
    $href = $attr['href'] ?? '#';
    $label = $attr['label'] ?? 'Submit Query';
    $size = $attr['size'] ?? 'md';

    // Get HTML
    $html = $this->tags['form.button'];
    $html = str_replace("~href~", $href, $html);
    $html = str_replace("~label~", tr($label), $html);
    $html = str_replace("~size~", $size, $html);

    // Return
    return $html;

}

/**
 * Box / panel
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function box(array $attr, string $text = ''):string
{

    // Get box header
    $header = '';
    if (preg_match("/<a:box_header(.*?)>(.*?)<\/a:box_header>/si", $text, $match)) { 
        $header_attr = view::parse_attr($match[1]);
        $header = $this->box_header($header_attr, $match[2]);
        $text = str_replace($match[0], '', $text);
    }

    // Get HTML
    $html = $this->tags['box'];
    $html = str_replace("~box_header~", $header, $html);
    $html = str_replace("~contents~", $text, $html);

    // Return
    return $html;

}

/**
 * Box / panel header 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function box_header(array $attr, string $text = ''):string
{ 

    // Set variables
    $title = $attr['title'] ?? '';

// Get HTML
    $html = $this->tags['box.header'];
    $html = str_replace("~title~", $title, $html);
    $html = str_replace("~contents~", $text, $html);

    // Return
    return $html;

}

/**
 * Input Box.
 *
 * Full width, short bar / container for things such as 
 * search boxes, or other elements to separate from page conents.  Example 
 * is Users->Manage User menu.
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function input_box(array $attr, string $text = ''):string
{

    // Get HTML
    $html = $this->tags['input_box'];
    $html = str_replace("~contents~", $text, $html);

    // Return
    return $html;

}

/**
 * Data table
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function data_table(array $attr, string $text = ''):string
{ 

    // Set merge variables
    $merge_vars = array(
        'table_id' => $attr['id'] ?? 'tbl_data', 
        'ajax_data' => $attr['ajax_data'] ?? '', 
        'form_name' => $attr['form_name'] ?? '', 
        'delete_button_label' => $attr['delete_button'] ?? '', 
        'search_label' => tr('Search'), 
        'total_columns' => 0,  
        'header_cells' => '', 
        'table_body' => ''
    );
    $html = $this->tags['data_table'];

    // Get search bar, if needed
    $search_bar = isset($attr['has_search']) && $attr['has_search'] == 1 ? $this->tags['data_table.search_bar'] : '';
    $html = str_replace("~search_bar~", $search_bar, $html);

    // Delete button
    $delete_button = $merge_vars['delete_button_label'] != '' ? $this->tags['data_table.delete_button'] : '';
    $html = str_replace("~delete_button~", $delete_button, $html);

    // Get pagination
    $pagination = isset($attr['has_pagination']) && $attr['has_pagination'] == 1 ? $this->pagination($attr, '') : '';
    $html = str_replace("~pagination~", $pagination, $html);

    // Go through header cells
    preg_match_all("/<a:th(.*?)>(.*?)<\/a:th>/", $text, $th_match, PREG_SET_ORDER);
    foreach ($th_match as $match) { 
        $th_attr = view::parse_attr($match[1]);
        $merge_vars['header_cells'] .= $this->data_table_th($th_attr, $match[2]);
        $merge_vars['total_columns']++;
    }

    // Get body of table
    if (preg_match("/<tbody>(.*?)<\/tbody>/si", $text, $match)) { 
        $merge_vars['table_body'] = $match[1];
    } else { 
        $merge_vars['table_body'] = preg_replace("/<a:th(.*?)>(.*?)<\/a:th>/", "", $text);
    }

    // Get table HTML
    foreach ($merge_vars as $key => $value) { 
        $html = str_replace("~$key~", $value, $html);
    }

    // Return
    return $html;

}

/**
 * Header cell of data table (a:th>)
 *
 * @param array $attr The attributes within the <a:th> tag.
 * @param string $name The name of the header column
 * 
 * @return string The resulting HTML code
 */
private function data_table_th(array $attr, string $name):string
{

    // Get variables
    $can_sort = $attr['can_sort'] ?? 0;
    $alias = $attr['alias'] ?? strtolower($name);

    // Get sort variables
    if ($can_sort == 1) { 
        $sort_asc = str_replace("~col_alias~", $alias, $this->tags['data_table.sort_asc']);
        $sort_desc = str_replace("~col_alias~", $alias, $this->tags['data_table.sort_desc']);
    } else { 
        $sort_asc = ''; $sort_desc = '';
    }

    // Replace HTML
    $html = $this->tags['data_table.th'];
    $html = str_replace("~sort_asc~", $sort_asc, $html);
    $html = str_replace("~sort_desc~", $sort_desc, $html);
    $html = str_replace("~name~", $name, $html);

    // Return
    return $html;

}

/**
 * Pagination links 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function pagination(array $attr, string $text = ''):string
{ 

    // Return if no rows
    if ($attr['total'] == 0) { return ''; }

    // Set variables
    $id = $attr['id'] ?? 'main';
    $page = $attr['page'];
    $total = $attr['total'];
    $rows_per_page = $attr['rows_per_page'];
    $total_pages = ceil($total / $rows_per_page);
    $start = ($page - 1) * $rows_per_page;

    // Get start / end pages
    $pages_left = ceil($total - ($page * $rows_per_page) / $rows_per_page);
    $start_page = ($pages_left > 7 && $page > 7) ? ($page - 7) : 1;
    $end_page = ($pages_left > 7) ? ($page + 15) - $page : $total_pages;

    // Get the href url
    if (isset($attr['href']) && $attr['href'] == 'route') { 
        $url = '/' . app::get_uri() . "?page=~page~";
    } else { 
        $ajaxdata = isset($attr['ajax_data']) ? $attr['ajax_data'] . '&page=~page~' : 'page=~page~';
        $url = "javascript:ajax_send('core/navigate_table', '$ajaxdata', 'none');";
    }

    // Return, if not enough rows
    if ($rows_per_page >= $total) { 
        return '';
    }


    // First page
    $display = $start_page > 1 ? 'visible' : 'none';
    $items = $this->pagination_item("&laquo;", $url, 1, $display);

    // Previous page
    $display = $page > 1 ? 'visible' : 'none';
    $items .= $this->pagination_item("&lt;", $url, ($page - 1), $display);

    // Go through pages
    $x=1;
    for ($page_num = $start_page; $page_num <= $end_page; $page_num++) { 
        if ($page_num > $total_pages) { break; }

        if ($page_num == $page) { 
            $items .= str_replace("~page~", $page_num, $this->tags['pagination.active_item']);
        } else {
            $items .= $this->pagination_item((string) $page, $url, (int) $page);
        }
    $x++; }

    // Next page
    $display = $total_pages > $page ? 'visible' : 'none';
    $items .= $this->pagination_item("&gt;", $url, ($page + 1), $display);

    // Last page
    $display = $total_pages > $end_page ? 'visible' : 'none';
    $items .= $this->pagination_item("&raquo;", $url, $end_page, $display);

    // Set merge variables
    $merge_vars = array(
        'start_record' => ($start + 1), 
        'end_record' => ($page * $rows_per_page), 
        'total_records' => $total, 
        'items' => $items
    );

    // Get HTML
    $html = $this->tags['pagination'];
    foreach ($merge_vars as $key => $value) { 
        $html = str_replace("~$key~", $value, $html);
    }

    // Return
    return $html;

}

/**
 * Pagination single list item.
 *
 * @param string $name The display name of the item.
 * @param string $url The URL to link the item to.
 * @param int $page The number number to link to.
 * @param string $display The display style of the item.  Defaults to 'visible'.
 *
 * @return string The resulting HTML code of the paginiation item.
 */
private function pagination_item(string $name, string $url, int $page, string $display = 'visible'):string
{

    // Get HTML
    $html = $this->tags['pagination.active_item'];
    $html = str_replace("~name~", $name, $html);
    $html = str_replace("~url~", str_replace("~page~", (string) $page, $url), $html);
    $html = str_replace("~display~", $display, $html);

    // Return
    return $html;

}

/**
 * Tab control
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function tab_control($attr, $text)
{ 

    // Initialize
    $tab_num = 1;
    $tab_html = '';
    $nav_html = '';

    // Get HTML
    $tabcontrol_tag = $this->tags['tab_control'];
    $navitem_tag = $this->tags['tab_control.nav_item'];
    $page_tag = $this->tags['tab_control.page'];
    $active = trim($this->tags['tab_control.css_active']);

    // Go through tab pages
    preg_match_all("/<a:tab_page(.*?)>(.*?)<\/a:tab_page>/si", $text, $tab_match, PREG_SET_ORDER);
    foreach ($tab_match as $tab) { 

        // Get name
        $name = preg_match("/name=\"(.+?)\"/", $tab[1], $name_match) ? $name_match[1] : 'Unknown Tab';

        // Add nav item
        $navitem = $navitem_tag;
        $navitem = str_replace("~tab_num~", $tab_num, $navitem);
        $navitem = str_replace("~active~", $active, $navitem);
        $navitem = str_replace("~name~", tr($name), $navitem);
        $nav_html .= $navitem;

        // Add tab page contents
        $page = $page_tag;
        $page = str_replace("~tab_num~", $tab_num, $page);
        $page = str_replace("~active~", $active, $page);
        $page = str_replace("~contents~", $tab[2], $page);
        $tab_html .= $page;

        // Update vars
        $tab_num++; $active = '';
    }

    // Finish HTML
    $html = $tabcontrol_tag;
    $html = str_replace("~nav_items~", $nav_html, $html);
    $html = str_replace("~tab_pages~", $tab_html, $html);

    // Return
    return $html;

}

/**
 * Boxlist
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function boxlist(array $attr, string $text = ''):string
{ 

    // Start html
    list($package, $alias) = explode(":", $attr['alias'], 2);
    $html = $this->tags['boxlist'];

    // Go through list items
    $items = '';
    $rows = db::query("SELECT * FROM internal_boxlists WHERE package = %s AND alias = %s ORDER BY order_num", $package, $alias);
    foreach ($rows as $row) { 
        $url = '/' . trim($row['href'], '/');
        $item_html = str_replace("~url~", $url, $this->tags['boxlist.item']);
    $item_html = str_replace("~title~", tr($row['title']), $item_html);
    $item_html = str_replace("~description~", tr($row['description']), $item_html);
        $items .= $item_html;
    }

    // Return
    $html = str_replace("~items~", $items, $html);
    return $html;

}

/**
 * date 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function date(array $attr, string $text = ''):string
{ 

    // Check for name
    if (!isset($attr['name'])) { 
        return "<b>ERROR:</b> No 'name' attribute within the e:date tab."; 
    }

    // Set variables
    $required = $attr['required'] ?? 0;
    $start_year = $attr['start_year'] ?? app::_config('core:start_year');
    $end_year = $attr['end_year'] ?? (date('Y') + 3);
    $value = $attr['value'] ?? '';
    if ($required == 1 && $value == '') { $value = date('Y-m-d'); }

    // Parse value
    if (preg_match("/(\d\d\d\d)-(\d\d)-(\d\d)/", $value, $match)) { 
        list($year, $month, $day) = explode("-", $value);
    } else { 
        list($year, $month, $day) = array(0, 0, 0); 
    }

    // Month HTML
    $month_options = $required == 1 ? '' : '<option value="0">----------</option>';
    for ($x = 1; $x <= 12; $x++) { 
        $chk = $x == $month ? 'selected="selected"' : '';
        $month_options .= "<option value=\"$x\" $chk>" . tr(date('F', mktime(0, 0, 0, $x, 1, 2000))) . "</option>";
    }

    // Day options
    $day_options = $required == 1 ? '' : '<option value="0">----</option>';
    for ($x = 1; $x <= 31; $x++) { 
        $chk = $x == $day ? 'selected="selected"' : '';
        $day_options .= "<option value=\"$x\" $chk>$x</option>";
    }

    // Year options
    $year_options = $required == 1 ? '' : '<option value="0">-----</option>';
    for ($x = $start_year; $x <= $end_year; $x++) { 
        $chk = $x == $year ? 'selected="selected"' : '';
        $year_options .= "<option value=\"$x\" $chk>$x</option>";
    }

    // Get HTML
    $html = $this->tags['form.date'];
    $html = str_replace("~name~", $attr['name'], $html);
    $html = str_replace("~month_options~", $month_options, $html);
    $html = str_replace("~day_options~", $day_options, $html);
    $html = str_replace("~year_options~", $year_options, $html);

    // Return
    return $html;

}

/**
 * Time
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function time(array $attr, string $text = ''):string
{

    // Check for name
    if (!isset($attr['name'])) { 
        return "<b>ERROR:</b> No 'name' attribute exists within the 'time' tab."; 
    }

    // Set variables
    $required = $attr['required'] ?? 0;
    $value = $attr['value'] ?? '00:00';

    // Parse value
    $vars = explode(":", $value);
    $hours = $vars[0] ?? 0;
    $mins = $vars[1] ?? 0;

    // Hour options
    $hour_options = $required == 1 ? '' : '<option value="">------</option>';
    for ($x=0; $x <= 23; $x++) { 
        $chk = $hours == $x ? 'selected="selected"' : '';
        $hour_options .= "<option value=\"$x\" $chk>" . sprintf("%2d", $x) . "</option>";
    }

    // Minute options
    $minute_options = $required == 1 ? '' : '<option value="">------</option>';
    foreach (array('00', '15', '30', '45') as $x) { 
        $chk = $x == $mins ? 'selected="selected"' : '';
        $minute_options .= "<option value=\"$x\" $chk>$x</option>";
    }

    // Get HTML
    $html = $this->tags['form.time'];
    $html = str_replace("~name~", $attr['name'], $html);
    $html = str_replace("~hour_options~", $hour_options, $html);
    $html = str_replace("~minute_options~", $minute_options, $html);

    // Return
    return $html;

}

/**
 * date interval 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function date_interval(array $attr, string $text = ''):string
{ 

    // Checks
    if (!isset($attr['name'])) { return "<b>ERROR:</b> The 'date_interval' tag does not have a 'name' attribute."; }

    // Set variables
    $name = $attr['name'];
    $add_time = $attr['add_time'] ?? 0;
    $value = $attr['value'] ?? '';

    // Get value
    if (preg_match("/^(\w)(\d+)$/", $value, $match)) { 
        $period = $match[1]; $num = $match[2];
    } else { $period = ''; $num = ''; }

    // Get periods
    $periods = $add_time == 1 ? array('I' => tr('Minutes'), 'H' => tr('Hours')) : array();
    $periods['D'] = tr('Days');
    $periods['W'] = tr('Weeks');
    $periods['M'] = tr('Months');
    $periods['Y'] = tr('Years');

    // Create period options
    $options = '';
    foreach ($periods as $abbr => $name) { 
        $chk = $abbr == $period ? 'selected="selected"' : '';
        $options .= "<option value=\"$abbr\" $chk>$name</option>";
    }

    // Get HTML
    $html = $this->tags['form.date_interval'];
    $html = str_replace("~name~", $attr['name'], $html);
    $html = str_replace("~num~", $num, $html);
    $html = str_replace("~period_options~", $options, $html);

    // Return
    return $html;

}

/**
 * Placeholder 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function placeholder(array $attr = [], string $text = '')
{ 

    // Check alias
    if (!isset($attr['alias'])) { return ''; }

    // Check redis
    $key = app::get_uri() . ':' . $attr['alias'];
    if (!$value = redis::hget('cms:placeholders', $key)) { 
        $value = '';
    }

    // Return
    return $value;

}

/**
 * reCaptcha
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function recaptcha(array $attr = [], string $text = '')
{ 

    // Check if enabled
    if (app::_config('core:recaptcha_site_key') == '') { 
        return ''; 
    }

    // Set HTML
    $html = "<div class=\"g-recaptcha\" data-sitekey=\"" . app::_config('core:recaptcha_site_key') . "\"></div>\n";

    // Return
    return $html;

}

/**
 * Dashboard
 *
* @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function dashboard($attr, $text)
{

    // Get the profile
    $client = app::make(dashboard::class);
    $profile = $client->get_profile();

    // Get dashboard HTML
    $html = $this->tags['dashboard'];
    $top_items = '';
    $right_items = '';

    // Go through top items
    if (!isset($profile['top_items'])) { $profile['top_items'] = array(); }
    foreach ($profile['top_items'] as $vars) { 

        // Get temp HTML
        $top_html = $this->tags['dashboard.top_item'];
        $top_html = str_replace("~title~", $vars['title'], $top_html);
        $top_html = str_replace("~contents~", $vars['contents'], $top_html);
        $top_html = str_replace("~divid~", $vars['divid'], $top_html);
        $top_html = str_replace("~panel_class~", $vars['panel_class'], $top_html);
        $top_items .= $top_html;
    }

    // Go through right items
    if (!isset($profile['right_items'])) { $profile['right_items'] = array(); }
    foreach ($profile['right_items'] as $vars) { 

        // Get HTML
        $right_html = $this->tags['dashboard.right_item'];
        $right_html = str_replace("~title~", $vars['title'], $right_html);
        $right_html = str_replace("~contents~", $vars['contents'], $right_html);
        $right_html = str_replace("~divid~", $vars['divid'], $right_html);
        $right_html = str_replace("~panel_class~", $vars['panel_class'], $right_html);
        $right_items .= $right_html;
    }

    // Replace dashboard html
    $html = str_replace("~top_items~", $top_items, $html);
    $html = str_replace("~right_items~", $right_items, $html);
    $html = str_replace("~profile_id~", $profile['id'], $html);
    $html = str_replace("~tabcontrol~", $profile['tabcontrol'], $html);


    // Return
    return $html;

}

/**
 * Dropdown list of all unread notifications 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.

 */
public function dropdown_alerts($attr, $text)
{ 

    // Set variables
    $redis_key = 'alerts:' . app::get_recipient();
    redis::ltrim($redis_key, 0, 9);

    // Go through alerts
    $html = '';
    $rows = redis::lrange($redis_key, 0, -1);
    foreach ($rows as $data) { 
        $row = json_decode($data, true);
        $tmp_html = $this->tags['dropdown.alert'];

        // Merge variables
        $tmp_html = str_replace("~url~", $row['url'], $tmp_html);
        $tmp_html = str_replace("~message~", $row['message'], $tmp_html);
        $tmp_html = str_replace("~time~", date::last_seen($row['time']), $tmp_html);
        $html .= $tmp_html;
    }

    // Return
    return $html;

}

/**
 * Dropdown list of messages 
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code.
 */
public function dropdown_messages($attr, $text)
{ 

    // Set variables
    $redis_key = 'messages:' . app::get_recipient();
    redis::ltrim($redis_key, 0, 9);

    // Go through alerts
    $html = '';
    $rows = redis::lrange($redis_key, 0, -1);
    foreach ($rows as $data) { 
        $row = json_decode($data, true);
        $tmp_html = $this->tags['dropdown.message'];

        // Merge variables
        $tmp_html = str_replace("~from~", $row['from'], $tmp_html);
        $tmp_html = str_replace("~url~", $row['url'], $tmp_html);
        $tmp_html = str_replace("~message~", $row['message'], $tmp_html);
        $tmp_html = str_replace("~time~", date::last_seen($row['time']), $tmp_html);
        $html .= $tmp_html;
    }

    // Return
    return $html;

}


}

