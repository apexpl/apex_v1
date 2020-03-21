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
    private $default_tags = 'YTo0NTp7czoxMDoiZm9ybV90YWJsZSI7czoxMDQ6Ijx0YWJsZSBib3JkZXI9IjAiIGNsYXNzPSJmb3JtX3RhYmxlIiBzdHlsZT0id2lkdGg6IH53aWR0aH47IGFsaWduOiB+YWxpZ25+OyI+CiAgICB+Y29udGVudHN+CjwvdGFibGU+CgoKIjtzOjE0OiJmb3JtX3RhYmxlLnJvdyI7czoxMzM6Ijx0cj4KICAgIDx0ZD48bGFiZWwgZm9yPSJ+bmFtZX4iPn5sYWJlbH46PC9sYWJlbD48L3RkPgogICAgPHRkPjxkaXYgY2xhc3M9ImZvcm0tZ3JvdXAiPgogICAgICAgIH5mb3JtX2ZpZWxkfgogICAgPC9kaXY+PC90ZD4KPC90cj4KCgoiO3M6MjA6ImZvcm1fdGFibGUuc2VwYXJhdG9yIjtzOjU1OiI8dHI+CiAgICA8dGQgY29sc3Bhbj0iMiI+PGg1Pn5sYWJlbH48L2g1PjwvdGQ+CjwvdHI+CgoKIjtzOjExOiJmb3JtLnN1Ym1pdCI7czoxNDA6IjxkaXYgY2xhc3M9InRleHQtbGVmdCI+CiAgICA8YnV0dG9uIHR5cGU9InN1Ym1pdCIgbmFtZT0ic3VibWl0IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImJ0biBidG4tcHJpbWFyeSBidG4tfnNpemV+Ij5+bGFiZWx+PC9idXR0b24+CjwvZGl2PgoKIjtzOjEwOiJmb3JtLnJlc2V0IjtzOjgzOiI8IS0tIDxidXR0b24gdHlwZT0icmVzZXQiIGNsYXNzPSJidG4gYnRuLXByaW1hcnkgYnRuLW1kIj5SZXNldCBGb3JtPC9idXR0b24+IC0tPgoKCiI7czoxMToiZm9ybS5idXR0b24iO3M6NjY6IjxhIGhyZWY9In5ocmVmfiIgY2xhc3M9ImJ0biBidG4tcHJpbmFyeSBidG4tfnNpemV+Ij5+bGFiZWx+PC9hPgoKCiI7czoxMjoiZm9ybS5ib29sZWFuIjtzOjIzMDoiPGRpdiBjbGFzcz0icmFkaW9mb3JtIj4KICAgIDxpbnB1dCB0eXBlPSJyYWRpbyIgbmFtZT0ifm5hbWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiB2YWx1ZT0iMSIgfmNoa195ZXN+IC8+IDxzcGFuPlllczwvc3Bhbj4gCiAgICA8aW5wdXQgdHlwZT0icmFkaW8iIG5hbWU9In5uYW1lfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgdmFsdWU9IjAiIH5jaGtfbm9+IC8+IDxzcGFuPk5vPC9zcGFuPiAKPC9kaXY+CgoiO3M6MTE6ImZvcm0uc2VsZWN0IjtzOjg5OiI8c2VsZWN0IG5hbWU9In5uYW1lfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgfndpZHRofiB+b25jaGFuZ2V+PgogICAgfm9wdGlvbnN+Cjwvc2VsZWN0PgoKCiI7czoxMjoiZm9ybS50ZXh0Ym94IjtzOjEyNToiPGlucHV0IHR5cGU9In50eXBlfiIgbmFtZT0ifm5hbWV+IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgaWQ9In5pZH4iIH5wbGFjZWhvbGRlcn4gfmFjdGlvbnN+IH52YWxpZGF0aW9ufiAvPgoKCgoiO3M6MTM6ImZvcm0udGV4dGFyZWEiO3M6MTEwOiI8dGV4dGFyZWEgbmFtZT0ifm5hbWV+IiBjbGFzcz0iZm9ybS1jb250cm9sIiBpZD0ifmlkfiIgc3R5bGU9IndpZHRoOiAxMDAlIiB+cGxhY2Vob2xkZXJ+Pn52YWx1ZX48L3RleHRhcmVhPgoKCiI7czo5OiJmb3JtLmRhdGUiO3M6MzQzOiI8c2VsZWN0IG5hbWU9In5uYW1lfl9tb250aCIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiAxMjBweDsgZmxvYXQ6IGxlZnQ7Ij4KICAgIH5tb250aF9vcHRpb25zfgo8L3NlbGVjdD4gCjxzZWxlY3QgbmFtZT0ifm5hbWV+X2RheX4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogMzBweDsgZmxvYXQ6IGxlZnQ7Ij4KICAgIH5kYXlfb3B0aW9uc34KPC9zZWxlY3Q+LCAKPHNlbGVjdCBuYW1lPSJ+bmFtZX5feWVhcn4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogNzBweDsgZmxvYXQ6IGxlZnQ7Ij4KICAgIH55ZWFyX29wdGlvbnN+Cjwvc2VsZWN0PgoKIjtzOjE4OiJmb3JtLmRhdGVfaW50ZXJ2YWwiO3M6MzcyOiI8ZGl2IGNsYXNzPSJmb3JtLWdyb3VwIj4KICAgIDxkaXYgY2xhc3M9ImNvbC1sZy04IiBzdHlsZT0icGFkZGluZy1sZWZ0OiAwIj4KICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ifm5hbWV+X251bSIgY2xhc3M9ImZvcm0tY29udHJvbCIgdmFsdWU9In5udW1+IiA+IAogICAgPC9kaXY+CiAgICA8ZGl2IGNsYXNzPSJjb2wtbGctNCIgc3R5bGU9InBhZGRpbmctcmlnaHQ6IDAiPgogICAgICAgIDxzZWxlY3QgbmFtZT0ifm5hbWV+X3BlcmlvZCIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiAxMDAlIiA+CiAgICAgICAgICAgIH5wZXJpb2Rfb3B0aW9uc34KICAgICAgICA8L3NlbGVjdD4KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKCgoiO3M6MzoiYm94IjtzOjE1MjoiPGRpdiBjbGFzcz0icGFuZWwgcGFuZWwtZGVmYXVsdCI+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1oZWFkaW5nIj4gfmJveF9oZWFkZXJ+PC9kaXY+CiAgICA8ZGl2IGNsYXNzPSJwYW5lbC1ib2R5Ij4KICAgICAgICB+Y29udGVudHN+CiAgICA8L2Rpdj4KPC9kaXY+CgoiO3M6MTA6ImJveC5oZWFkZXIiO3M6MTE2OiI8c3BhbiBzdHlsZT0iYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkICMzMzMzMzM7IG1hcmdpbi1ib3R0b206IDhweDsiPgogICAgPGgzPn50aXRsZX48L2gzPgogICAgfmNvbnRlbnRzfgo8L3NwYW4+CgoKCiI7czo4OiJjYWxsb3V0cyI7czoxNDc6IjxkaXYgY2xhc3M9ImFsZXJ0IGFsZXJ0LX5jc3NfYWxpYXN+Ij48cD4KICAgIDxidXR0b24gdHlwZT0iYnV0dG9uIiBjbGFzcz0iY2xvc2UiIGRhdGEtZGlzbWlzcz0iYWxlcnQiPiZ0aW1lczs8L2J1dHRvbj4KICAgIH5tZXNzYWdlc34KPC9wPjwvZGl2PgoKCiI7czoxMjoiY2FsbG91dHMuY3NzIjtzOjEwMjoiWwogICAgInN1Y2Nlc3MiOiAic3VjY2VzcyIsIAogICAgImVycm9yIjogImVycm9yIiwgCiAgICAid2FybmluZyI6ICJ3YXJuaW5nIiwgCiAgICAiaW5mbyI6ICJpbmZvIgpdCgoKIjtzOjEzOiJjYWxsb3V0cy5pY29uIjtzOjEyMzoiWwogICAgInN1Y2Nlc3MiOiAiZmEgZmEtY2hlY2siLCAKICAgICJlcnJvciI6ICJmYSBmYS1iYW4iLCAKICAgICJ3YXJuaW5nIjogImZhIGZhLXdhcm5pbmciLCAKICAgICJpbmZvIjogImZhIGZhLWluZm8iCl0KCgoKIjtzOjEwOiJuYXYuaGVhZGVyIjtzOjIyOiI8bGk+PGgzPn5uYW1lfjwvbGk+CgoKIjtzOjEwOiJuYXYucGFyZW50IjtzOjg5OiI8bGk+CiAgICA8YSBocmVmPSJ+dXJsfiI+fmljb25+IH5uYW1lfjwvYT4KICAgIDx1bD4KICAgICAgICB+c3VibWVudXN+CiAgICA8L3VsPgo8L2xpPgoKCiI7czo4OiJuYXYubWVudSI7czo0NToiPGxpPjxhIGhyZWY9In51cmx+Ij5+aWNvbn5+bmFtZX48L2E+PC9saT4KCgoKIjtzOjExOiJ0YWJfY29udHJvbCI7czoxNjM6Igo8ZGl2IGNsYXNzPSJuYXYtdGFicy1jdXN0b20iPgogICAgPHVsIGNsYXNzPSJuYXYgbmF2LXRhYnMiPgogICAgICAgIH5uYXZfaXRlbXN+CiAgICA8L3VsPgoKICAgIDxkaXYgY2xhc3M9InRhYi1jb250ZW50Ij4KICAgICAgICB+dGFiX3BhZ2VzfgogICAgPC9kaXY+CjwvZGl2PgoKCgoiO3M6MjA6InRhYl9jb250cm9sLm5hdl9pdGVtIjtzOjgxOiI8bGkgY2xhc3M9In5hY3RpdmV+Ij48YSBocmVmPSIjdGFifnRhYl9udW1+IiBkYXRhLXRvZ2dsZT0idGFiIj5+bmFtZX48L2E+PC9saT4KCgoiO3M6MTY6InRhYl9jb250cm9sLnBhZ2UiO3M6NzQ6IjxkaXYgY2xhc3M9InRhYi1wYW5lIH5hY3RpdmV+IiBpZD0idGFifnRhYl9udW1+Ij4KICAgIH5jb250ZW50c34KPC9kaXY+CgoKIjtzOjIyOiJ0YWJfY29udHJvbC5jc3NfYWN0aXZlIjtzOjExOiJhY3RpdmUKCgoKCiI7czoxMDoiZGF0YV90YWJsZSI7czo0MjA6Ijx0YWJsZSBjbGFzcz0idGFibGUgdGFibGUtYm9yZGVyZWQgdGFibGUtc3RyaXBlZCB0YWJsZS1ob3ZlciIgaWQ9In50YWJsZV9pZH4iPgogICAgPHRoZWFkPgogICAgICAgIH5zZWFyY2hfYmFyfgogICAgPHRyPgogICAgICAgIH5oZWFkZXJfY2VsbHN+CiAgICA8L3RyPgogICAgPC90aGVhZD4KCiAgICA8dGJvZHkgaWQ9In50YWJsZV9pZH5fdGJvZHkiIGNsYXNzPSJib2R5dGFibGUiPgogICAgICAgIH50YWJsZV9ib2R5fgogICAgPC90Ym9keT4KCiAgICA8dGZvb3Q+PHRyPgogICAgICAgIDx0ZCBjb2xzcGFuPSJ+dG90YWxfY29sdW1uc34iIGFsaWduPSJyaWdodCI+CiAgICAgICAgICAgIH5kZWxldGVfYnV0dG9ufgogICAgICAgICAgICB+cGFnaW5hdGlvbn4KICAgICAgICA8L3RkPgogICAgPC90cj48L3Rmb290Pgo8L3RhYmxlPgoKCiI7czoxMzoiZGF0YV90YWJsZS50aCI7czo3MzoiPHRoIGNsYXNzPSJib3hoZWFkZXIiPiA8c3Bhbj5+bmFtZX48L3NwYW4+IH5zb3J0X2Rlc2N+IH5zb3J0X2FzY348L3RoPgoKCiI7czoxOToiZGF0YV90YWJsZS5zb3J0X2FzYyI7czoyMDk6IjxhIGhyZWY9ImphdmFzY3JpcHQ6YWpheF9zZW5kKCdjb3JlL3NvcnRfdGFibGUnLCAnfmFqYXhfZGF0YX4mc29ydF9jb2w9fmNvbF9hbGlhc34mc29ydF9kaXI9YXNjJywgJ25vbmUnKTsiIGJvcmRlcj0iMCIgdGl0bGU9IlNvcnQgQXNjZW5kaW5nIH5jb2xfYWxpYXN+IiBjbGFzcz0iYXNjIj4KICAgIDxpIGNsYXNzPSJmYSBmYS1zb3J0LWFzYyI+PC9pPgo8L2E+CgoKIjtzOjIwOiJkYXRhX3RhYmxlLnNvcnRfZGVzYyI7czoyMTI6IjxhIGhyZWY9ImphdmFzY3JpcHQ6YWpheF9zZW5kKCdjb3JlL3NvcnRfdGFibGUnLCAnfmFqYXhfZGF0YX4mc29ydF9jb2w9fmNvbF9hbGlhc34mc29ydF9kaXI9ZGVzYycsICdub25lJyk7IiBib3JkZXI9IjAiIHRpdGxlPSJTb3J0IERlY2VuZGluZyB+Y29sX2FsaWFzfiIgY2xhc3M9ImRlc2MiPgogICAgPGkgY2xhc3M9ImZhIGZhLXNvcnQtZGVzYyI+PC9pPgo8L2E+CgoKIjtzOjIxOiJkYXRhX3RhYmxlLnNlYXJjaF9iYXIiO3M6NDUyOiI8dHI+CiAgICA8dGQgc3R5bGU9ImJvcmRlci10b3A6MXB4IHNvbGlkICNjY2MiIGNvbHNwYW49In50b3RhbF9jb2x1bW5zfiIgYWxpZ249InJpZ2h0Ij4KICAgICAgICA8ZGl2IGNsYXNzPSJmb3Jtc2VhcmNoIj4KICAgICAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIG5hbWU9InNlYXJjaF9+dGFibGVfaWR+IiBwbGFjZWhvbGRlcj0ifnNlYXJjaF9sYWJlbH4uLi4iIGNsYXNzPSJmb3JtLWNvbnRyb2wiIHN0eWxlPSJ3aWR0aDogMjEwcHg7Ij4gCiAgICAgICAgICAgIDxhIGhyZWY9ImphdmFzY3JpcHQ6YWpheF9zZW5kKCdjb3JlL3NlYXJjaF90YWJsZScsICd+YWpheF9kYXRhficsICdzZWFyY2hffnRhYmxlX2lkficpOyIgY2xhc3M9ImJ0biBidG4tcHJpbWFyeSBidG4tbWQiPjxpIGNsYXNzPSJmYSBmYS1zZWFyY2giPjwvaT48L2E+CiAgICAgICAgPC9kaXY+CiAgICA8L3RkPgo8L3RyPgoKCiI7czoyNDoiZGF0YV90YWJsZS5kZWxldGVfYnV0dG9uIjtzOjIyMjoiPGEgaHJlZj0iamF2YXNjcmlwdDphamF4X2NvbmZpcm0oJ0FyZSB5b3Ugc3VyZSB5b3Ugd2FudCB0byBkZWxldGUgdGhlIGNoZWNrZWQgcmVjb3Jkcz8nLCAnY29yZS9kZWxldGVfcm93cycsICd+YWpheF9kYXRhficsICcnKTsiIGNsYXNzPSJidG4gYnRuLXByaW1hcnkgYnRuLW1kIGJvdG9udGVzdCIgc3R5bGU9ImZsb2F0OiBsZWZ0OyI+fmRlbGV0ZV9idXR0b25fbGFiZWx+PC9hPgoKCgoKIjtzOjEwOiJwYWdpbmF0aW9uIjtzOjIzMDoiPHNwYW4gaWQ9InBnbnN1bW1hcnlffmlkfiIgc3R5bGU9InZlcnRpY2FsLWFsaWduOiBtaWRkbGU7IGZvbnQtc2l6ZTogOHB0OyBtYXJnaW4tcmlnaHQ6IDdweDsiPgogICAgPGI+fnN0YXJ0X3JlY29yZH4gLSB+ZW5kX3JlY29yZH48L2I+IG9mIDxiPn50b3RhbF9yZWNvcmRzfjwvYj4KPC9zcGFuPgoKPHVsIGNsYXNzPSJwYWdpbmF0aW9uIiBpZCA9InBnbl9+aWR+Ij4KICAgIH5pdGVtc34KPC91bD4KCgoiO3M6MTY6InBhZ2VpbmF0aW9uLml0ZW0iO3M6NjU6IjxsaSBzdHlsZT0iZGlzcGxheTogfmRpc3BsYXl+OyI+PGEgaHJlZj0ifnVybH4iPn5uYW1lfjwvYT48L2xpPgoKIjtzOjIyOiJwYWdpbmF0aW9uLmFjdGl2ZV9pdGVtIjtzOjQzOiI8bGkgY2xhc3M9ImFjdGl2ZSI+PGE+fnBhZ2V+PC9hPjwvbGk+CgoKCgoKIjtzOjE0OiJkcm9wZG93bi5hbGVydCI7czoxNjU6IjxsaSBjbGFzcz0ibWVkaWEiPgogICAgPGRpdiBjbGFzcz0ibWVkaWEtYm9keSI+CiAgICAgICAgPGEgaHJlZj0ifnVybH4iPn5tZXNzYWdlfgogICAgICAgIDxkaXYgY2xhc3M9InRleHQtbXV0ZWQgZm9udC1zaXplLXNtIj5+dGltZX48L2Rpdj4KCTwvYT4KICAgIDwvZGl2Pgo8L2xpPgoKCiI7czoxNjoiZHJvcGRvd24ubWVzc2FnZSI7czozNjE6IjxsaSBjbGFzcz0ibWVkaWEiPgogICAgPGRpdiBjbGFzcz0ibWVkaWEtYm9keSI+CgogICAgICAgIDxkaXYgY2xhc3M9Im1lZGlhLXRpdGxlIj4KICAgICAgICAgICAgPGEgaHJlZj0ifnVybH4iPgogICAgICAgICAgICAgICAgPHNwYW4gY2xhc3M9ImZvbnQtd2VpZ2h0LXNlbWlib2xkIj5+ZnJvbX48L3NwYW4+CiAgICAgICAgICAgICAgICA8c3BhbiBjbGFzcz0idGV4dC1tdXRlZCBmbG9hdC1yaWdodCBmb250LXNpemUtc20iPn50aW1lfjwvc3Bhbj4KICAgICAgICAgICAgPC9hPgogICAgICAgIDwvZGl2PgoKICAgICAgICA8c3BhbiBjbGFzcz0idGV4dC1tdXRlZCI+fm1lc3NhZ2V+PC9zcGFuPgogICAgPC9kaXY+CjwvbGk+CgoKCgoiO3M6NzoiYm94bGlzdCI7czo0MjoiPHVsIGNsYXNzPSJib3hsaXN0Ij4KICAgIH5pdGVtc34KPC91bD4KCgoKIjtzOjEwOiJmb3JtLnBob25lIjtzOjI0NDoiPGRpdiBjbGFzcz0iZm9ybS1ncm91cCI+CiAgICA8c2VsZWN0IG5hbWU9In5uYW1lfl9jb3VudHJ5IiBjbGFzcz0iZm9ybS1jb250cm9sIGNvbC1sZy0yIj4KICAgICAgICB+Y291bnRyeV9jb2RlX29wdGlvbnN+CiAgICA8L3NlbGVjdD4gCiAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ifm5hbWV+IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImZvcm0tY29udHJvbCBjb2wtbGctMTAiICB+cGxhY2Vob2xkZXJ+Pgo8L2Rpdj4KCiI7czoxMToiZm9ybS5hbW91bnQiO3M6MjAwOiI8c3BhbiBzdHlsZT0iZmxvYXQ6IGxlZnQ7Ij5+Y3VycmVuY3lfc2lnbn48L3NwYW4+IAo8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ifm5hbWV+IiB2YWx1ZT0ifnZhbHVlfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgc3R5bGU9IndpZHRoOiA2MHB4OyBmbG9hdDogbGVmdDsiIH5wbGFjZWhvbGRlcn4gZGF0YS1wYXJzbGV5LXR5cGU9ImRlY2ltYWwiPgoKCiI7czo5OiJmb3JtLnRpbWUiO3M6MjMwOiI8c2VsZWN0IG5hbWU9In5uYW1lfl9ob3VyIiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDYwcHg7IGZsb2F0OiBsZWZ0OyI+CiAgICB+aG91cl9vcHRpb25zfgo8L3NlbGVjdD4gOiAKPHNlbGVjdCBuYW1lPSJ+bmFtZX5fbWluIiBjbGFzcz0iZm9ybS1jb250cm9sIiBzdHlsZT0id2lkdGg6IDYwcHg7IGZsb2F0OiBsZWZ0OyI+CiAgICB+bWludXRlX29wdGlvbnN+Cjwvc2VsZWN0PgoKCiI7czo5OiJpbnB1dF9ib3giO3M6MTE3OiI8ZGl2IGNsYXNzPSJwYW5lbCBwYW5lbC1kZWZhdWx0IHNlYXJjaF91c2VyIj4KICAgIDxkaXYgY2xhc3M9InBhbmVsLWJvZHkiPgogICAgICAgIH5jb250ZW50c34KICAgIDwvZGl2Pgo8L2Rpdj4KCgoKCgoiO3M6MTI6ImJveGxpc3QuaXRlbSI7czo5NToiPGxpPgogICAgPGEgaHJlZj0ifnVybH4iPgogICAgICAgIDxiPn50aXRsZX48L2I+PGJyIC8+CiAgICAgICAgfmRlc2NyaXB0aW9ufgogICAgPC9hPgo8L2xpPgoKCgoiO3M6OToiZGFzaGJvYXJkIjtzOjM4NToiCjxkaXYgY2xhc3M9InJvdyBib3hncmFmIj4KICAgIH50b3BfaXRlbXN+CjwvZGl2PgoKPGRpdiBjbGFzcz0icGFuZWwgcGFuZWwtZmxhdCI+CiAgICA8YTpmdW5jdGlvbiBhbGlhcz0iZGlzcGxheV90YWJjb250cm9sIiB0YWJjb250cm9sPSJjb3JlOmRhc2hib2FyZCI+CjwvZGl2PgoKPGRpdiBjbGFzcz0ic2lkZWJhciBzaWRlYmFyLWxpZ2h0IGJnLXRyYW5zcGFyZW50IHNpZGViYXItY29tcG9uZW50IHNpZGViYXItY29tcG9uZW50LXJpZ2h0IGJvcmRlci0wIHNoYWRvdy0wIG9yZGVyLTEgb3JkZXItbWQtMiBzaWRlYmFyLWV4cGFuZC1tZCI+CiAgICA8ZGl2IGNsYXNzPSJzaWRlYmFyLWNvbnRlbnQiPgogICAgICAgIH5yaWdodF9pdGVtc34KICAgIDwvZGl2Pgo8L2Rpdj4KCiI7czoxODoiZGFzaGJvYXJkLnRvcF9pdGVtIjtzOjM3NzoiPGRpdiBjbGFzcz0iY29sLWxnLTQiPgogICAgPGRpdiBjbGFzcz0ifnBhbmVsX2NsYXNzfiI+CiAgICAgICAgPGRpdiBjbGFzcz0icGFuZWwtYm9keSI+CgogICAgICAgICAgICA8aDMgY2xhc3M9Im5vLW1hcmdpbiI+fmNvbnRlbnRzfjwvaDM+CiAgICAgICAgICAgICAgICB+dGl0bGV+CiAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8ZGl2IGNsYXNzPSJ0ZXh0LW11dGVkIHRleHQtc2l6ZS1zbWFsbCI+fmNvbnRlbnRzfjwvZGl2PgogICAgICAgIDwvZGl2PgogICAgICAgIDxkaXYgY2xhc3M9ImNvbnRhaW5lci1mbHVpZCI+CiAgICAgICAgICAgIDxkaXYgaWQ9In5kaXZpZH4iPjwvZGl2PgogICAgICAgIDwvZGl2PgogICAgPC9kaXY+CjwvZGl2PgoKCgoiO3M6MjA6ImRhc2hib2FyZC5yaWdodF9pdGVtIjtzOjQwNzoiPGRpdiBjbGFzcz0iY2FyZCI+CiAgICA8ZGl2IGNsYXNzPSJjYXJkLWhlYWRlciBiZy10cmFuc3BhcmVudCBoZWFkZXItZWxlbWVudHMtaW5saW5lIj4KICAgICAgICA8c3BhbiBjbGFzcz0iY2FyZC10aXRsZSBmb250LXdlaWdodC1zZW1pYm9sZCI+fnRpdGxlfjwvc3Bhbj4KICAgIDwvZGl2PgogICAgPGRpdiBjbGFzcz0iY2FyZC1ib2R5Ij4KICAgICAgICA8dWwgY2xhc3M9Im1lZGlhLWxpc3QiPgogICAgICAgICAgICA8bGkgY2xhc3M9Im1lZGlhIj4KICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9Im1lZGlhLWJvZHkiPgogICAgICAgICAgICAgICAgICAgIH5jb250ZW50c34KICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8L2xpPgogICAgICAgIDwvdWw+CiAgICA8L2Rpdj4KPC9kaXY+CgoKCgoiO3M6MTE6ImZvcm0uZWRpdG9yIjtzOjIzNDoiPHRleHRhcmVhIG5hbWU9In5uYW1lfiIgY2xhc3M9ImZvcm0tY29udHJvbCIgaWQ9In5uYW1lfiIgc3R5bGU9IndpZHRoOiAxMDAlIiB+cGxhY2Vob2xkZXJ+Pn52YWx1ZX48L3RleHRhcmVhPgo8c2NyaXB0IHNyYz0iL3BsdWdpbnMvY2tlZGl0b3IvY2tlZGl0b3IuanMiIHR5cGU9InRleHQvamF2YXNjcmlwdCI+PC9zY3JpcHQ+CjxzY3JpcHQ+Q0tFRElUT1IucmVwbGFjZSgnfm5hbWV+Jyk7PC9zY3JpcHQ+CgoKIjt9';


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
 * editor (ckeditor)
 *
 * @param array $attr All attributes passed within the HTML tag.
 * @param string $text The text between the opening and closing tags, if applicable.
 *
 * @return string The resulting HTML code. 
 */
public function editor(array $attr, string $text = ''):string
{ 

    // Perform checks
    if (!isset($attr['name'])) { return "<v>ERROR:</b>:  No 'name' attribute within the ft_editor field."; }

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
    $html = $this->tags['form.editor'];
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

