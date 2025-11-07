<?php

use IvyTranslate\Enums\IvyResourceType;
use IvyTranslate\IvyProject;

beforeEach(function () {
    $this->rootPath = realpath(__DIR__.'/../testdata/IvyProjectTest/lang');
    config(['app.locale' => 'en']);
    IvyProject::init($this->rootPath);
});

it('should find the expected resources', function () {
    $inst = IvyProject::current();

    // Should be pulled from config
    expect($inst->sourceLocale())->toBe('en');

    // Shold match dir minus source
    expect($inst->targetLocales())->toBe(['es_MX', 'fr', 'it']);

    // Repeat for each target locale
    $targets = [
        'en' => [
            [
                'path' => '/en/messages.json',
                'type' => IvyResourceType::LARAVEL_JSON,
                'namespace' => 'messages',
            ],
        ],
        'es_MX' => [
            [
                'path' => '/es_MX/messages.php',
                'type' => IvyResourceType::LARAVEL_PHP,
                'namespace' => 'messages',
            ],
        ],
        'fr' => [
            [
                'path' => '/fr/backend.php',
                'type' => IvyResourceType::LARAVEL_PHP,
                'namespace' => 'backend',
            ],
            [
                'path' => '/fr/frontend.php',
                'type' => IvyResourceType::LARAVEL_PHP,
                'namespace' => 'frontend',
            ],
        ],
        'it' => [
            [
                'path' => '/it.json',
                'type' => IvyResourceType::LARAVEL_JSON,
                'namespace' => null,
            ],
        ],
    ];

    foreach ($targets as $locale => $resourceExpectations) {
        $resources = $inst->getResourcesFor($locale);
        expect($resources)->toHaveCount(count($resourceExpectations));
        foreach ($resourceExpectations as $i => $resourceExpect) {
            expect(str_replace($this->rootPath, '', $resources[$i]->path))->toBe($resourceExpect['path']);
            expect($resources[$i]->type)->toBe($resourceExpect['type']);
            expect($resources[$i]->namespace)->toBe($resourceExpect['namespace']);
        }
    }
});

it('should find the expected keys', function () {
    $inst = IvyProject::current();
    $keys = $inst->keys();

    expect($keys)->toEqual([
        'messages.pizza_fact_1' => [
            'en' => 'Pizza crust gets crispier when baked on a stone.',
            'es_MX' => 'La corteza de la pizza se vuelve más crujiente al hornearla en piedra.',
            'fr' => null,
            'it' => null,
        ],
        'backend.pizza_fact_4' => [
            'fr' => 'La pâte fine cuit rapidement dans un four chaud.',
            'en' => null,
            'es_MX' => null,
            'it' => null,
        ],
        'frontend.pizza_fact_1' => [
            'fr' => "La pâte à pizza devient plus croustillante lorsqu'elle cuit sur une pierre.",
            'en' => null,
            'es_MX' => null,
            'it' => null,
        ],
        'pizza_fact_1' => [
            'it' => 'La crosta della pizza diventa più croccante quando cuoce su una pietra.',
            'en' => null,
            'es_MX' => null,
            'fr' => null,
        ],
    ]);
});
