<?php

namespace MakeWeb\Shipper;

class PluginFileParser
{
    public function getPluginVersion($filename)
    {
        return $this->getPluginAttribute($filename, 'Version');
    }

    public function getPluginName($filename)
    {
        return $this->getPluginAttribute($filename, 'Plugin Name');
    }

    public function getPluginAttribute($filename, $attribute)
    {
        $versionLine = array_filter($this->getPluginData($filename), function ($line) use ($attribute) {
            return $this->stringContains($line, $attribute.':');
        });

        $versionLine = reset($versionLine);

        return trim(str_replace('*', '', str_replace($attribute.':', '', $versionLine)));
    }

    public function getPluginData($filename)
    {
        return explode("\n", file_get_contents($filename));
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    public function stringContains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
