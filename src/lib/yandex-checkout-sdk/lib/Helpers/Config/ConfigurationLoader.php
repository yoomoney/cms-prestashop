<?php

namespace YaMoney\Helpers\Config;

class ConfigurationLoader implements ConfigurationLoaderInterface
{
    private $configParams;

    public function load($filePath = null)
    {
        if ($filePath) {
            $data = file_get_contents($filePath);
        } else {
            $data = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "configuration.json");
        }

        $paramsArray = json_decode($data, true);

        $this->configParams = $paramsArray;

        return $this;
    }

    public function getConfig()
    {
        return $this->configParams;
    }
}