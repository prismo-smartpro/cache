<?php

namespace SmartPRO\Technology;

use DateTime;
use Exception;


class CacheControl
{
    /*** @var string|null */
    protected ?string $path = null;
    /*** @var string|mixed|null */
    protected ?string $extension = null;

    /**
     * @param $path
     * @param string $extension
     */
    public function __construct($path, string $extension = "cache")
    {
        $this->path = $path;
        $this->extension = $extension;
    }

    /**
     * @param $name
     * @return bool|null
     */
    public function delete($name): ?bool
    {
        $cacheFile = $this->getPath($name);
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        return null;
    }

    /**
     * @param $name
     * @param $value
     * @param int $expires_in
     * @return bool
     */
    public function set($name, $value, int $expires_in = 300): bool
    {
        try {

            if (!file_exists($this->path)) {
                throw new Exception("The destination folder does not exist");
            }

            $archive = $this->getPath($name);

            $data = array(
                "created_at" => $this->currentTime(),
                "expires_in" => $this->currentTime($expires_in),
                "content" => $value
            );

            $data = $this->serialize($data);

            if (file_put_contents($archive, $data) === false) {
                throw new Exception("Error creating cache cache");
            }

            return true;

        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param $name
     * @param callable|null $function
     * @param int $expires_in
     * @return null
     */
    public function get($name, ?callable $function = null, int $expires_in = 10)
    {
        try {
            $cacheFile = $this->getPath($name);
            if (!file_exists($cacheFile)) {
                if (!is_null($function) && is_callable($function)) {
                    $data = $function();
                    $this->set($name, $data, $expires_in);
                    return $data;
                } else {
                    throw new Exception("The file does not exist");
                }
            }

            $fileContent = file_get_contents($cacheFile);
            if ($fileContent === false) {
                throw new Exception("Error reading the file");
            }

            $data = unserialize($fileContent);

            if (isset($data->expires_in) && $data->expires_in <= $this->currentTime()) {
                unlink($cacheFile);
                if (!is_null($function) && is_callable($function)) {
                    $data = $function();
                    $this->set($name, $data, $expires_in);
                    return $data;
                } else {
                    throw new Exception("The cache has already expired");
                }
            }

            if (empty($data->content)) {
                throw new Exception("The cache file is invalid");
            }

            return $data->content;

        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * @param $data
     * @return string
     */
    private function serialize($data): string
    {
        if (is_array($data)) {
            $data = (object)$data;
        }
        return serialize($data);
    }


    /**
     * @param $name
     * @return string|null
     */
    private function getPath($name): ?string
    {
        $extension = $this->getExtension();
        $pathName = rtrim($this->path, "/");
        return "{$pathName}/{$name}.{$extension}";
    }

    /**
     * @return string|null
     */
    private function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * @param $update
     * @return string
     */
    private function currentTime($update = null): string
    {
        $dateTime = new DateTime();
        if ($update) {
            $dateTime->modify("+{$update} minutes");
        }
        return $dateTime->format("Y-m-d H:i:s");
    }
}