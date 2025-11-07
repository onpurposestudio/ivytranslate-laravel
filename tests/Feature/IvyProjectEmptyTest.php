<?php

use IvyTranslate\IvyProject;

beforeEach(function () {
    $this->rootPath = realpath(__DIR__.'/../testdata/IvyProjectEmptyTest/lang');
    // $this->rootPath = realpath('/Users/apresley/Development/coolify/lang');
    config(['app.locale' => 'en']);
    IvyProject::init($this->rootPath);
});

it('should list empty keys as expected', function () {
    $inst = IvyProject::current();

    // This should list all keys
    expect($inst->keys())->toEqual([
        'key_in_both' => [
            'en' => 'My name is Aaron',
            'es' => 'Me llamo Aaron',
        ],
        'key_in_en' => [
            'en' => 'Hi, world',
            'es' => null,
        ],
        'key_in_es' => [
            'es' => 'Hola, mundo',
            'en' => null,
        ],
    ]);

    // This should only list keys with at least 1 empty value
    expect($inst->keysWithEmptyValues())->toEqual([
        'key_in_en' => [
            'en' => 'Hi, world',
            'es' => null,
        ],
        'key_in_es' => [
            'es' => 'Hola, mundo',
            'en' => null,
        ],
    ]);
});
