<?php

namespace MakeWeb\Shipper;

use MakeWeb\Shipper\Exceptions\FileAlreadyExistsException;

class Shipper
{
    public $arguments;

    public $dir;

    public $fileManager;

    public $zipper;

    public $pluginFileParser;

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
        $this->finalZipPath = $finalDestination.'-'.$this->version.'.zip';

        $this->output('');
        $this->output('Zip file will be saved to ', false);
        $this->green($this->finalZipPath);
        $this->output('');

        // Create the shipped plugin directory if it does not yet exist
        if (!file_exists($shippedPluginDirectory)) {
            $this->line('Creating directory: '.$shippedPluginDirectory);
            mkdir($shippedPluginDirectory);
        }

        // Create the plugin release directory if it does not yet exist
        if (!file_exists($releaseDirectory)) {
            $this->line('Creating directory: '.$releaseDirectory);
            mkdir($releaseDirectory);
        }

        // Copy the files to their shipped location
        $this->line('Copying plugin files to: '.$finalDestination);
        $this->fileManager->xcopy($dir, $finalDestination);

        // Remove studio.json file if it exists
        if (file_exists($finalDestination.'/studio.json')) {
            $this->line('Removing file: '.$finalDestination.'/studio.json');
            unlink($finalDestination.'/studio.json');
        }

        // Run composer install
        $this->line('Running composer install');
        echo shell_exec("rm -R $finalDestination/vendor");
        echo shell_exec("composer install --prefer-dist --no-plugins --no-dev -d $finalDestination --ansi");
        $this->output('');

        // Delete skipped files
        $this->line('Deleting files named in .shipignore file');
        foreach ($this->getSkippedFiles() as $filename) {
            if (empty($filename)) {
                continue;
            }
            $path = $finalDestination.'/'.$filename;
            $this->output('Deleting: '.$path);
            echo shell_exec("rm -Rf $path");
        }

        $this->line('Zipping up remaining files');
        $this->zipper->zipDirectory($finalDestination, $this->finalZipPath);

        // Delete the unzipped directory
        $this->line('Deleting the build directory');
        shell_exec("rm -Rf $finalDestination");

        $this->output('Success! ', false);
        $this->green($this->finalZipPath, false);
        $this->output(' ready to ship!');

        if (file_exists($deployScript = $this->dir.'/deploy.php') && $this->shouldDeploy()) {
            require $deployScript;
        }
    }

    public function getConfig()
    {
        if (!file_exists($this->configFilePath)) {
            return [
                'shippedPluginDirectory' => $this->fileManager->getHomeDirectory(),
            ];
        }

        return include $this->configFilePath;
    }

    public function getSkippedFiles()
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

    public function getVersion()
    {
        return $this->pluginFileParser->getPluginVersion($this->pluginFilePath());
    }

    public function getPluginName()
    {
        return $this->pluginFileParser->getPluginName($this->pluginFilePath());
    }

    public function baseDirectory()
    {
        return basename($this->dir);
    }

    public function pluginFilePath()
    {
        if (file_exists($pluginFilePath = $this->dir.'/'.$this->baseDirectory().'.php')) {
            return $pluginFilePath;
        }

        if (file_exists($themeFilePath = $this->dir.'/style.css')) {
            return $themeFilePath;
        }
    }

    public function output($string, $lineBreak = true, $colorCode = '37')
    {
        echo "\033[{$colorCode}m$string\033[0m";

        if ($lineBreak) {
            echo "\n";
        }
    }

    public function line($string)
    {
        $this->output($string);
        $this->output('');
    }

    public function green($message, $lineBreak = true)
    {
        $this->output($message, $lineBreak, '32');
    }

    public function cyan($message, $lineBreak = true)
    {
        $this->output($message, $lineBreak, '36');
    }

    public function red($message, $lineBreak = true)
    {
        $this->output($message, $lineBreak, '31');
    }

    public function getArgument()
    {
        return isset($this->arguments[1]) ? $this->arguments[1] : null;
    }

    /**
     * Handle the publish argument.
     **/
    public function publish()
    {
        try {
            $this->fileManager->publishShipIgnoreFile($this->dir);
        } catch (FileAlreadyExistsException $e) {
            $this->red($e->getMessage());
        }

        $this->green('.shipignore file published');
    }

    public function zipFileName()
    {
        return $this->baseDirectory().'-'.$this->version.'.zip';
    }

    public function shouldDeploy()
    {
        return $this->flagExists('d', 'deploy');
    }

    public function flagExists($shortFlag, $longFlag = null)
    {
        return count(array_filter($this->getAllFlags(), function ($flag) use ($shortFlag, $longFlag) {
            return $flag === $shortFlag || $flag === $longFlag;
        })) > 0;
    }

    public function getAllFlags()
    {
        return array_map(function ($flag) {
            return str_replace('-', '', $flag);
        }, array_filter($this->arguments, function ($argument) {
            return $this->argumentIsFlag($argument);
        }));
    }

    public function argumentIsFlag($argument)
    {
        return $argument[0] == '-';
    }
}
