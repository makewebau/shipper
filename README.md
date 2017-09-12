# MakeWeb Shipper

Easily zip up your Wordpress plugin into a .zip file ready for shipping.

## Installation

Installation is best done with Composer.


    composer require --dev makeweb/shipper


Note: The `--dev` flag is important as this is a development dependency which should not be shipped with your distributed plugin files.

## Usage

Once installed, you will have access to a new terminal command `vendor/bin/ship` which will automatically produce the shippable zip file for your plugin. To run the shipper script, from the root directory of your project just type:

    vendor/bin/ship
    
### Terminal Alias

You might want to create an alias so you can use something like `ship` to save remembering and typing all those additional characters. To do this on a Mac, add the following to your `~/.bash_profile` file (or create the file and add it, if it does not already exist):

    alias ship="vendor/bin/ship"

Then type `source ~/.bash_profile` or open a new terminal window to make the alias available. You will only need to do this once.

After this you will be able to run the shipper script from the root directory of your project with just:

    ship
    
### .shipignore

To automatically remove files or directories from the distribution version of your plugin before zipping, list each file or directory on a new line in a file called `.shipignore` in your project root. To copy the default `.shipignore` file to your project root, use the command `vendor/bin/ship publish` (`ship publish` if you have an alias set up).
