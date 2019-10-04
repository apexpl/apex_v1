
# Apex Training - Create Database Tables

Many of you will probably groan, but at Apex we strongly believe that SQL database schemas should be written
in, well...  SQL.  If you do not know SQL, it is an extremely easy language to learn the basics, and can
dramatically improve the architecture, performance, and stability of your software.  You will notice there is
a blank file at */etc/training/install.sql*, and this file is executed against the database upon
installation of the package.

Open the */etc/training/install.sql* file, and enter the following contents:

~~~


DROP TABLE IF EXISTS lotteries;

CREATE TABLE lotteries (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    userid INT NOT NULL, 
    amount DECIMAL(16,8) NOT NULL, 
    total_entries INT NOT NULL, 
    date_added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE
) engine=InnoDB;

~~~

Now simply connect to mySQL via terminal, and copy and paste the above SQL into the mySQL prompt to create the
necessary tables.


### Next

Now that we have a small database structure, let's move on to [Creating a Library](library.md).


