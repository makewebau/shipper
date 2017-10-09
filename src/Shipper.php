<?php

namespace MakeWeb\Shipper;

use MakeWeb\Shipper\Exceptions\FileAlreadyExistsException;

class Shipper
{
    protected $arguments;

    protected $dir;

    protected $fileManager;

    protected $zipper;

    protected $pluginFileParser;

    public function __construct()
    {
        $this->fileManager = new FileManager();
        $this->pluginFileParser = new PluginFileParser();
        $this->zipper = new Zipper();
    }

    /**
     * Prepare the Wordpress plugin contained in the given directory for deployment
     * by producing a shippable .zip file.
     **/
    public function ship($dir, $arguments)
    {
        $this->dir = $dir;

        $this->arguments = $arguments;

        if ($this->getArgument() === 'publish') {
            $this->publish();

            return;
        }

        $this->version = $this->getVersion();

        $this->output('');
        $this->output('Preparing ', false);
        $this->green($this->getPluginName(), false);
        $this->output(' version ', false);
        $this->green($this->version, false);
        $this->output(' for shipping.');

        // Get the config files
        $this->configFilePath = $this->fileManager->getHomeDirectory().'/.makeweb/config.php';

        // Get the shipped plugin directory
        $shippedPluginDirectory = $this->getConfig()['shippedPluginDirectory'];

        $releaseDirectory = $shippedPluginDirectory.'/'.$this->baseDirectory();
        $finalDestination = $releaseDirectory.'/'.$this->baseDirectory();
        $finalZipPath = $finalDestination.'-'.$this->version.'.zip';

        $this->output('');
        $this->output('Zip file will be saved to ', false);
        $this->green($finalZipPath);
        $this->output('');

        // Create the shipped plugin directory if it does not yet exist
        if (!file_exists($shippedPluginDirectory)) {
            $this->output('Creating directory: '.$shippedPluginDirectory);
            mkdir($shippedPluginDirectory);
        }

        // Create the plugin release directory if it does not yet exist
        if (!file_exists($releaseDirectory)) {
            $this->output('Creating directory: '.$releaseDirectory);
            mkdir($releaseDirectory);
        }

        // Copy the files to their shipped location
        $this->output('Copying plugin files to: '.$finalDestination);
        $this->fileManager->xcopy($dir, $finalDestination);

        // Remove studio.json file if it exists
        if (file_exists($finalDestination.'/studio.json')) {
            $this->output('Removing file: '.$finalDestination.'/studio.json');
            unlink($finalDestination.'/studio.json');
        }

        // Run composer install
        $this->output('Running composer install');
        echo shell_exec("rm -R $finalDestination/vendor");
        echo shell_exec("composer install --prefer-dist --no-plugins --no-dev -d $finalDestination --ansi");

        // Delete skipped files
        $this->output('Deleting files named in .shipignore file');
        foreach ($this->getSkippedFiles() as $filename) {
            if (empty($filename)) {
                continue;
            }
            $path = $finalDestination.'/'.$filename;
            $this->output('Deleting: '.$path);
            echo shell_exec("rm -Rf $path");
        }

        $this->output('Zipping up remaining files');
        $this->zipper->zipDirectory($finalDestination, $finalZipPath);

        // Delete the unzipped directory
        $this->output('Deleting the build directory');
        shell_exec("rm -Rf $finalDestination");

        $this->output('');
        $this->green('Success!');
        $this->output('');
        $this->green($finalZipPath.' ready to ship!');
    }

    protected function getConfig()
    {
        if (!file_exists($this->configFilePath)) {
            return [
                'shippedPluginDirectory' => $this->fileManager->getHomeDirectory(),
            ];
        }

        return include $this->configFilePath;
    }

    protected function getSkippedFiles()
    {
        if (!file_exists($this->dir.'/.shipignore')) {
            return [];
        }

        $skippedFiles = [];

        if ($file = fopen($this->dir.'/.shipignore', 'r')) {
            while (!feof($file)) {
                $skippedFiles[] = trim(fgets($file));
            }
            fclose($file);
        }

        return $skippedFiles;
    }

    protected function getVersion()
    {
        return $this->pluginFileParser->getPluginVersion($this->pluginFilePath());
    }

    protected function getPluginName()
    {
        return $this->pluginFileParser->getPluginName($this->pluginFilePath());
    }

    protected function baseDirectory()
    {
        return basename($this->dir);
    }

    protected function pluginFilePath()
    {
        return $this->dir.'/'.$this->baseDirectory().'.php';
    }

    protected function output($string, $lineBreak = true, $colorCode = '37')
    {
        echo "\033[{$colorCode}m$string\033[0m";

        if ($lineBreak) {
            echo "\n";
        }
    }

    protected function green($message, $lineBreak = true)
    {
        $this->output($message, $lineBreak, '32');
    }

    protected function cyan($message, $lineBreak = true)
    {
        $this->output($message, $lineBreak, '36');
    }

    protected function red($message, $lineBreak = true)
    {
        $this->output($message, $lineBreak, '31');
    }

    protected function getArgument()
    {
        return isset($this->arguments[1]) ? $this->arguments[1] : null;
    }

    /**
     * Handle the publish argument.
     **/
    protected function publish()
    {
        try {
            $this->fileManager->publishShipIgnoreFile($this->dir);
        } catch (FileAlreadyExistsException $e) {
            $this->red($e->getMessage());
        }

        $this->green('.shipignore file published');
    }
}
