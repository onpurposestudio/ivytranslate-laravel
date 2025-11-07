<?php

namespace IvyTranslate;

use Illuminate\Support\Collection;
use IvyTranslate\Enums\IvyResourceType;

class IvyResource
{
    public IvyResourceType $type;

    /**
     * @var Collection<int, \IvyTranslate\IvyKey>
     */
    protected Collection $keys;

    public function __construct(
        public string $locale,
        public string $path,
        public ?string $namespace = null,
    ) {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'json':
                $this->type = IvyResourceType::LARAVEL_JSON;
                break;
            case 'php':
                $this->type = IvyResourceType::LARAVEL_PHP;
                break;
            default:
                throw new \Exception("Unsupported file extension: $ext");
        }
    }

    public function keys()
    {
        if (isset($this->keys)) {
            return $this->keys;
        }

        switch ($this->type) {
            case IvyResourceType::LARAVEL_PHP:
                $rawKeys = include $this->path;
                break;
            case IvyResourceType::LARAVEL_JSON:
                $contents = file_get_contents($this->path);
                $rawKeys = json_decode($contents, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("JSON decode error in {$this->path}: ".json_last_error_msg());
                }
                break;
            default:
                throw new \Exception("Unsupported resource type: {$this->type->name}");
        }

        $this->keys = collect([]);

        foreach ($rawKeys as $name => $value) {
            $this->keys->add(new IvyKey(
                $name, $value,
            ));
        }

        return $this->keys;
    }
}
