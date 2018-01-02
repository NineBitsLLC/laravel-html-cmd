# laravel-html-cmd
Create laravel project without install composer and laravel-installer and use artisan command without SSH connection.

### USAGE
To use:
* Place the master.pxp file in a folder accessible via HTTP.
* Ask your secret key to the const SECRET
* Set the public folder to const LARAVEL_PUPLIC

### EXTEND
To extend the list of standart commands, edit:
* const COMPOSER_COMMAND_LIST,
* const LARAVEL_ARTISAN_COMMAND_LIST.

To create other commands, edit:
* Master::Init() method.
