<?php
namespace Nerdman\Configuration;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class Configuration
{
    /** @var array */
    private $config = [];

    /**
     * Finds and load all PHP file in the given directory, overwriting duplicate configuration keys
     *
     * @param $directory
     */
    public function load(string $directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)),
            '/^(.+)\.php$/',
            RegexIterator::MATCH
        );

        foreach ($iterator as $file) {
            /** @var \SplFileObject $file */
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $dir = substr($file->getPath(), strlen($directory) + 1);
            $filename = strtolower(substr($file->getFilename(), 0, -4));

            $keys = [];
            if ($dir) {
                $keys = explode('/', $dir);
            }

            $config = &$this->config;
            foreach ($keys as $key) {
                $key = strtolower($key);

                if (!isset($config[$key])) {
                    $config[$key] = [];
                }

                $config = &$config[$key];
            }

            if (!isset($config[$filename])) {
                $config[$filename] = [];
            }

            $addConfiguration = require $file;

            if (is_array($addConfiguration)) {
                $config[$filename] = array_replace_recursive($config[$filename], require $file);
            } else {
                $config[$filename] = $addConfiguration;
            }
        }
    }

    /**
     * Get a value from the configuration store
     *
     * @param string $key
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public function get(string $key, $defaultValue = null)
    {
        $keys = explode('.', $key);

        $config = &$this->config;
        foreach ($keys as $key) {
            $config = &$config[$key] ?? null;
            if ($config === null) {
                break;
            }
        }

        return $config ?? $defaultValue;
    }

    /**
     * Get all configuration keys as an array
     *
     * @return mixed[]
     */
    public function getAll()
    {
        return $this->config;
    }

    /**
     * Set a configuration key
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value)
    {
        $keys = explode('.', $key);
        $finalKey = array_pop($keys);

        $config = &$this->config;
        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                $config[$key] = [];
            }

            $config = &$config[$key];
        }

        $config[$finalKey] = $value;
    }

    /**
     * Remove a configuration key
     *
     * @param string $key
     */
    public function delete(string $key)
    {
        $keys = explode('.', $key);
        $finalKey = array_pop($keys);

        $config = &$this->config;
        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                return;
            }

            $config = &$config[$key];
        }

        if (isset($config[$finalKey])) {
            unset($config[$finalKey]);
        }
    }
}

