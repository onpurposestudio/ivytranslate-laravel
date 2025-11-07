<?php

namespace IvyTranslate;

use Exception;
use Illuminate\Support\Facades\Log;

class IvyProject
{
    public static IvyProject $instance;

    protected array $resourceFilesMap;

    protected array $resourceObjectMap;

    protected array $keyDataMap;

    public function __construct(
        protected ?string $path = null,
    ) {
        // Get our source based on app configs
        $sourceLocale = $this->sourceLocale();

        if (! $sourceLocale) {
            throw new Exception("Couldn't determine source locale. Is `app.locale` config set?");
        }

        // Load a simple map of our resource paths
        $this->loadResourceMap();

        // Most things won't work if we can't find a resource
        // for our source locale
        if (! isset($this->resourceFilesMap[$sourceLocale])) {
            throw new Exception("No resource found matching source locale (`$sourceLocale`)");
        }
    }

    /**
     * Get an array of all locales: the source locale followed by the target locales.
     *
     * @return array<int, string> An array of all locale codes, source first
     */
    public function allLocales(): array
    {
        // Merge source locale and (sorted) target locales into a single array
        return [
            $this->sourceLocale(),
            ...$this->targetLocales(),
        ];
    }

    public function getResources(): array
    {
        $out = [];

        foreach ($this->allLocales() as $thisLocale) {
            $out = array_merge($out, $this->getResourcesFor($thisLocale));
        }

        return $out;
    }

    /**
     * Returns an array of IvyResource objects for the given locale. Stores the
     * object in a map for quick reference later if needed.
     *
     * @return array<int, IvyResource> An array of IvyResource objects for the given locale
     */
    public function getResourcesFor(string $locale): array
    {
        // Have we already processed this?
        if (isset($this->resourceObjectMap[$locale])) {
            return $this->resourceObjectMap[$locale];
        }

        // Do we have this locale in our file map?
        if (! isset($this->resourceFilesMap[$locale])) {
            throw new \Exception("Unknown locale `$locale`");
        }

        // Begin our map
        if (! isset($this->resourceObjectMap)) {
            $this->resourceObjectMap = [];
        }

        // Create an empty space for this locale, or reset previous values
        $this->resourceObjectMap[$locale] = [];

        // Loop reach resource path for this locale and instantiate
        foreach ($this->resourceFilesMap[$locale] as $resourcePath) {
            $this->resourceObjectMap[$locale][] = new IvyResource(
                $locale,
                $resourcePath['path'],
                $resourcePath['namespace'],
            );
        }

        return $this->resourceObjectMap[$locale];
    }

    /**
     * Returns all keys from all resources files
     */
    public function keys()
    {
        $map = [];

        $allLocales = $this->allLocales();

        // Loop every locale so we know which resources to expect
        foreach ($allLocales as $thisLocale) {

            // Get each resource for this locale
            foreach ($this->getResourcesFor($thisLocale) as $localeResource) {

                // Get every key for this resource
                foreach ($localeResource->keys() as $thisResourceKey) {

                    // Build our key's name
                    $keyName = $thisResourceKey->name;
                    if ($localeResource->namespace) {
                        $keyName = $localeResource->namespace.'.'.$keyName;
                    }

                    // Create an empty target if we haven't seen this key name before
                    if (! isset($map[$keyName])) {
                        $map[$keyName] = [];
                    }

                    // Have we seen this key for this locale already?
                    if (isset($map[$keyName][$thisLocale])) {
                        Log::warning("Ivy found duplicate keys within the same locale (`$keyName` → `$thisLocale`).\nOnly the first instance will be used.");
                        continue;
                    }

                    $map[$keyName][$thisLocale] = $thisResourceKey->value;
                }
            }
        }

        // Now we loop again to backfill any empties
        foreach ($allLocales as $thisLocale) {
            foreach ($map as $keyName => $values) {
                $map[$keyName][$thisLocale] = $values[$thisLocale] ?? null;
            }
        }

        // Sort the map by key name (the hash)
        ksort($map);

        $this->keyDataMap = $map;

        // Return
        return $this->keyDataMap;
    }

    public function keysWithEmptyValues()
    {
        $emptyMap = [];
        $allKeys = $this->keys();

        // Loop all of our keys
        foreach ($allKeys as $keyName => $values) {
            foreach ($values as $val) {
                // Just 1 empty is enough
                if ($val === null) {
                    $emptyMap[$keyName] = $values;
                    continue;
                }
            }
        }

        return $emptyMap;
    }

    /**
     * Get the list of all target locales, i.e., all locales present except for the source locale.
     *
     * @return array<int, string> An array of locale codes, e.g. ['es_MX', 'fr', 'it']
     */
    public function targetLocales(): array
    {
        $sourceLocale = $this->sourceLocale();

        // Get all locales present in the resource map, then remove the source locale
        $targetLocales = array_diff(array_keys($this->resourceFilesMap), [$sourceLocale]);

        // Return a numerically indexed and sorted array of unique target locales
        $result = array_values(array_unique($targetLocales));
        sort($result);

        return $result;
    }

    /**
     * Get the source locale for the project, based on configuration.
     *
     * @return string The source locale code, e.g. 'en'
     */
    public function sourceLocale(): string
    {
        // Retrieve the locale from config: ivytranslate.source_locale, fallback to app.locale, then environment variable
        return (string) config(
            'ivytranslate.source_locale',
            config('app.locale', env('APP_LOCALE'))
        );
    }

    /**
     * Returns the current shared instance of this IvyProject, if any
     */
    public static function current(): IvyProject
    {
        if (! self::$instance) {
            throw new \Exception('IvyProject not yet initialized');
        }

        return self::$instance;
    }

    /**
     * Initializes a new IvyProject for this codebase.
     */
    public static function init(?string $path = null): IvyProject
    {
        self::$instance = new self($path);
        return self::$instance;
    }

    /**
     * Reloads the IvyProject in case any files have changed
     */
    public static function reload(): void
    {
        if (! self::$instance) {
            throw new \Exception('IvyProject not yet initialized');
        }

        self::$instance = new IvyProject(self::$instance->path);
    }

    /**
     * Performs a quick map of this project's lang files and associated
     * resource files. Doesn't parse anything or instantiate any objects.
     */
    protected function loadResourceMap(): void
    {
        $path = $this->path ?? lang_path();
        if (! is_dir($path)) {
            throw new Exception("Couldn't find lang path");
        }

        $this->resourceFilesMap = [];

        // Loop each file/directory in the lang dir
        foreach (scandir($path) as $item) {
            if (in_array($item, ['.', '..'])) {
                continue;
            }

            $fullPath = $path.DIRECTORY_SEPARATOR.$item;

            // If it's a file, treat the filename (without extension) as locale
            if (! is_dir($fullPath)) {
                $fileExtension = pathinfo($fullPath, PATHINFO_EXTENSION);
                if (! in_array($fileExtension, ['php', 'json'])) {
                    continue;
                }
                $locale = pathinfo($item, PATHINFO_FILENAME);
                $this->resourceFilesMap[$locale][] = [
                    'path' => $fullPath,
                    'namespace' => null,
                ];
                continue;
            }

            // If it's a directory, treat dir name as locale, and scan its files
            foreach (scandir($fullPath) as $langItem) {
                if (in_array($langItem, ['.', '..'])) {
                    continue;
                }
                $langFullPath = $fullPath.DIRECTORY_SEPARATOR.$langItem;
                $fileExtension = pathinfo($langFullPath, PATHINFO_EXTENSION);
                if (! in_array($fileExtension, ['php', 'json'])) {
                    continue;
                }
                $locale = $item;
                $this->resourceFilesMap[$locale][] = [
                    'path' => $langFullPath,
                    'namespace' => pathinfo($langItem, PATHINFO_FILENAME),
                ];
            }
        }
    }
}
