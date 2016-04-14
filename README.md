# Slot Calendar, Resource reservation #

The aim of this web app is to create a page that shows resources and allows users to reserve one resource for them. 

Examples: 
- office hours
- any kind of limited day reservation system (showing everyday would be too much for the aim of this app)
- presentations certain days, only 3 people can present per day

Users are greeted with this page:

They enter their email address (that's how the system matches them) and their name, and the pin code so random people can't play with this.

![Screenshot](readme/screenshot-1.PNG?raw=true "Screenshot 1")
# Admin Pages #

/admin page has it's own password that's defined in the public/index.php.

You can view the already picked dates, and delete an entry (users can't delete once selected)

![Screenshot](readme/screenshot-2.PNG?raw=true "Screenshot 2")

You can edit the available slots and how many people can sign up on that slot.

![Screenshot](readme/screenshot-3.PNG?raw=true "Screenshot 3")

You can edit who can sign up to the slots

![Screenshot](readme/screenshot-4.PNG?raw=true "Screenshot 4")

You can practically change any part of the UI from this form.

![Screenshot](readme/screenshot-5.PNG?raw=true "Screenshot 5")

# Initial Configuration and requirements #
PHP, MySQL

You need to create a database in MySQL and the app will create it's tables at the initial run.

`DEFINE("DOMAINTENANCE", 0);`

`DEFINE("HOSTNAME_DBCONN", "localhost");`

`DEFINE("DATABASE_DBCONN", "databaseName");`

`DEFINE("USERNAME_DBCONN", "databaseUserName");`

`DEFINE("PASSWORD_DBCONN", "databasePassword");`

`DEFINE("ADMIN_ACCOUNT_PASSWORD", "verysecurepassword");`

