# MakeWeb Shipper

Easily zip up your Wordpress plugin into a .zip file ready for shipping.

## Installation

Installation is best done with Composer.

```
composer require --dev makeweb/shipper
```

Note: The `--dev` flag is important as this is a development dependency which should not be shipped with your distributed plugin files.

The installation process will copy two new files `.shipignore` and `ship` to your plugin directory. These are needed to run the script. It is safe to commit these to version control as they will be removed by the ship script so they are not shipped with your distributed plugin files.

