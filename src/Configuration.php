<?php
namespace Nerdman\Configuration;

class Configuration
{
    private array $config = [];

    /**
     * Finds and load all PHP file in the given directory, overwriting duplicate configuration keys
     */
    public function load(string $directory): void
    {
        if (!\is_dir($directory)) {
            return;
        }

        $directory = \rtrim($directory, DIRECTORY_SEPARATOR);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            /** @var \SplFileObject $file */
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $dir = \substr($file->getPath(), \strlen($directory) + 1);
            $filename = \strtolower(\substr($file->getFilename(), 0, -4));

            $keys = [];
            if ($dir) {
                $keys = \explode('/', $dir);
            }

            $config = &$this->config;
            foreach ($keys as $key) {
                $key = \strtolower($key);

                if (!isset($config[$key])) {
                    $config[$key] = [];
                }

                $config = &$config[$key];
            }

            if (!isset($config[$filename])) {
                $config[$filename] = [];
            }

            $addConfiguration = require $file;

            if (\is_array($addConfiguration)) {
                $config[$filename] = \array_replace_recursive($config[$filename], require $file);
            } else {
                $config[$filename] = $addConfiguration;
            }
        }
    }

    /**
     * Get a value from the configuration store
     */
    public function get(string $key, mixed $defaultValue = null): mixed
    {
        $keys = \explode('.', $key);

        $config = &$this->config;
        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                break;
            }

            $config = &$config[$key];
        }

        return $config ?? $defaultValue;
    }

    /**
     * Get all configuration keys as an array
     */
    public function getAll(): array
    {
        return $this->config;
    }

    /**
     * Set a configuration key
     */
    public function set(string $key, mixed $value): void
    {
        $keys = \explode('.', $key);
        $finalKey = \array_pop($keys);

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
     */
    public function delete(string $key): void
    {
        $keys = \explode('.', $key);
        $finalKey = \array_pop($keys);

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

