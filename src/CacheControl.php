<?php

namespace SmartPRO\Technology;

use DateTime;
use Exception;


class CacheControl
{

    const JSON = "json";
    const SERIALIZE = "serialize";
    const DOCUMENT = "document";

    /*** @var string|null */
    protected ?string $path = null;
    /*** @var string|mixed|null */
    protected ?string $extension = null;
    protected ?string $type = "serialize";

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

            if ($this->type === "serialize") {
                $data = $this->serialize([
                    "created_at" => $this->currentTime(),
                    "expires_in" => $this->currentTime($expires_in),
                    "content" => $value
                ]);
            } elseif ($this->type === "json") {
                $data = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                $data = $value;
            }

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
     * @param callable|null $callable
     * @param int $expires_in
     * @return mixed
     */
    public function has($name, ?callable $callable, int $expires_in = 30): mixed
    {
        $getCache = $this->get($name);
        if ($getCache !== null) {
            return $getCache;
        }

        $data = $callable();
        $this->set($name, $data, $expires_in);
        return $data;
    }


    /**
     * @param $name
     * @return mixed|null
     */
    public function get($name): mixed
    {
        try {
            $cacheFile = $this->getPath($name);
            if (!file_exists($cacheFile)) {
                throw new Exception("The file does not exist");
            }

            $fileContent = file_get_contents($cacheFile);
            if ($fileContent === false) {
                throw new Exception("Error reading the file");
            }

            if ($this->type === "json") {
                $data = json_decode($fileContent);
            } elseif ($this->type === "serialize") {
                @$data = unserialize($fileContent);
            } else {
                $data = $fileContent;
            }

            if ($this->type === "serialize" and $data === false) {
                throw new Exception("The cache type is not valid");
            }

            if (isset($data->expires_in) && $data->expires_in <= $this->currentTime()) {
                unlink($cacheFile);
                throw new Exception("The cache has already expired");
            }

            return ($data->content ?? $data);

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
     * @param int|null $update
     * @return string
     */
    private function currentTime(?int $update = null): string
    {
        $dateTime = new DateTime();
        if ($update) {
            $dateTime->modify("+{$update} minutes");
        }
        return $dateTime->format("Y-m-d H:i:s");
    }

    /**
     * @param $content
     * @return mixed
     */
    private function unSerialize($content): mixed
    {
        $unSerialized = @unserialize($content);
        if ($unSerialized !== false) {
            return $unSerialized;
        }
        return $content;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }
}