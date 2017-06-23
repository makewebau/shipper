<?php

namespace MakeWeb\Shipper;

class Shipper
{
    protected $dir;

    protected $fileManager;

    protected $zipper;

    public function __construct()
    {
        $this->fileManager = new FileManager();
        $this->zipper = new Zipper();
    }

    /**
     * Prepare the Wordpress plugin contained in the given directory for deployment
     * by producing a shippable .zip file.
     **/
    public function ship($dir)
    {
        $this->dir = $dir;

        // Get the config files
        $this->configFilePath = $this->fileManager->getHomeDirectory().'/.makeweb/config.php';

        // Get the shipped plugin directory
        $shippedPluginDirectory = $this->getConfig()['shippedPluginDirectory'];

        $releaseDirectory = $shippedPluginDirectory.'/'.$this->baseDirectory();
        $finalDestination = $releaseDirectory.'/'.$this->baseDirectory();

        // Create the shipped plugin directory if it does not yet exist
        if (!file_exists($shippedPluginDirectory)) {
            mkdir($shippedPluginDirectory);
        }

        // Create the plugin release directory if it does not yet exist
        if (!file_exists($releaseDirectory)) {
            mkdir($releaseDirectory);
        }

        // Copy the files to their shipped location
        $this->fileManager->xcopy($dir, $finalDestination);

        // Remove studio.json file if it exists
        if (file_exists($finalDestination.'/studio.json')) {
            unlink($finalDestination.'/studio.json');
        }

        // Run composer install
        echo shell_exec("rm -R $finalDestination/vendor");
        echo shell_exec("composer install --prefer-dist --no-plugins --no-dev -d $finalDestination --ansi");

        // Delete skipped files
        foreach ($this->getSkippedFiles() as $filename) {
            if (empty($filename)) {
                continue;
            }
            $path = $finalDestination.'/'.$filename;
            echo shell_exec("rm -Rf $path");
        }

        $this->zipper->zipDirectory($finalDestination, $finalDestination.'-'.$this->getVersion().'.zip');

        // Delete the unzipped directory
        shell_exec("rm -Rf $finalDestination");
    }

    protected function getConfig()
    {
        if (!file_exists($this->configFilePath)) {
            return [
                'shippedPluginDirectory' => $this->fileManager->getHomeDirectory(),
            ];
        }

        return include CONFIG_FILE_PATH;
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
        return '1.0.0';
    }

    protected function baseDirectory()
    {
        return basename($this->dir);
    }
}
