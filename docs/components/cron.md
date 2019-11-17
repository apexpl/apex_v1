
# Crontab Job Component

&nbsp; | &nbsp;
**Description:** | Automatically executes at a specified time interval (eg. every 30 minutes, 7 days, etc.), and is used for any automated processes you need to run.
**Create Command:** | `./apex create cron PACKAGE:ALIAS`
**File Location:** | /src/PACKAGE?cron/ALIAS>php
**Namespace:** | `\apex\PACKAGE\cron\ALIAS`


## Properties

Only contains the one `$time_interval` property, which defines how often to execute the 
crontab job.  Formatted in `PNN` where `P` is one of the following:

- I = Minutes
- H = Hours
- D = Days
- W = Weeks
- M = Months
- Y = Years

Then `NN` is simply the number pertaining to the period.  For example, `M30` means every 30 minutes, `W1` means 
every week, and so on.


## Methods

Below describes all methods avialable in this class.


### `process()`

**Description:** Simply contains whatever PHP code you need executed automatically at the pre-defined interval.

