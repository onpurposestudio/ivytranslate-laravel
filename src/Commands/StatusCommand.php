<?php

namespace IvyTranslate\Commands;

use Illuminate\Console\Command;
use IvyTranslate\IvyProject;

class StatusCommand extends Command
{
    protected $signature = 'ivy:status';

    protected $description = 'Check the status of your translations within the codebase';

    public function handle()
    {
        IvyProject::init();
        $this->renderResourceFilesTable();
        $this->renderEmptyKeysTable();

        return Command::SUCCESS;
    }

    protected function renderSummaryTable()
    {
        $inst = IvyProject::current();

        $targetLocales = $inst->targetLocales();
        $resources = $inst->getResources();
        $allKeys = $inst->keys();

        $table = $this->getOutput()->createTable();
        $table->setHeaders([
            'Source Locale',
            'Target Locales',
            'Resource Files',
            'Unique Keys',
        ])
            ->setRows([
                [
                    $inst->sourceLocale(),
                    implode(', ', $targetLocales).' ('.count($targetLocales).')',
                    count($resources),
                    count($allKeys),
                ],
            ])
            ->setStyle('box')
            ->setColumnMaxWidth(1, 60);
        $table->setVertical();
        $table->render();
    }

    protected function renderResourceFilesTable()
    {
        $inst = IvyProject::current();
        $resources = $inst->getResources();

        $rows = [];
        $sourceLocale = $inst->sourceLocale();
        foreach ($resources as $resource) {
            $localeLabel = $resource->locale === $sourceLocale ? $resource->locale.'*' : $resource->locale;
            // Get total keys count for this resource file
            $totalKeys = 0;
            $keys = $resource->keys();
            if (is_countable($keys)) {
                $totalKeys = count($keys);
            } elseif (method_exists($keys, 'count')) {
                $totalKeys = $keys->count();
            }
            $rows[] = [
                $localeLabel,
                $resource->path,
                $totalKeys,
            ];
        }

        $this->line('Here are all of the resource files we find within your project');
        $table = $this->getOutput()->createTable();
        $table->setHeaders(['Locale', 'Resource File', 'Total Keys'])
            ->setRows($rows)
            ->setStyle('box');
        $table->render();
    }

    protected function renderEmptyKeysTable()
    {
        $inst = IvyProject::current();
        $keysWithEmpty = $inst->keys();

        if (empty($keysWithEmpty)) {
            $this->line('<info>No keys with missing translations found.</info>');
            return;
        }

        $this->line('');
        $this->line('');
        $this->line('Here is a table outline all of your unique keys, and which have a value for the given locale');

        // Collect all locales that are present in the keys map so the columns match the order as in IvyProject
        $locales = $inst->allLocales();

        // Build table headers: [Key, locale1, locale2, ...]
        $headers = array_merge(['Key'], $locales);

        // Table rows: each row is: [key name, val for locale1, locale2, ...]
        $rows = [];
        foreach ($keysWithEmpty as $keyName => $perLocale) {
            $row = [$keyName];
            foreach ($locales as $locale) {
                $val = $perLocale[$locale] ?? null;
                $row[] = $val !== null && $val !== '' ? '✅' : '❌';
            }
            $rows[] = $row;
        }

        $table = $this->getOutput()->createTable();
        $table->setHeaders($headers)
            ->setRows($rows)
            ->setStyle('box');
        $table->render();
    }

    protected function renderResourceMapTable()
    {
        $inst = IvyProject::current();

        $rows = [
            [
                "{$inst->sourceLocale()}*",
                implode("\n", array_map(fn ($r) => $r->path, $inst->getResourcesFor($inst->sourceLocale()) ?? []))."\n",
            ],
        ];

        foreach ($inst->targetLocales() as $locale) {
            $rows[] = [
                $locale,
                implode("\n", array_map(fn ($r) => $r->path, $inst->getResourcesFor($locale) ?? []))."\n",
            ];
        }

        $headers = ['Locale', 'Path(s)'];
        $table = $this->getOutput()->createTable();

        // Add an extra row to span all columns
        // Use a Symfony table separator and then a row with array that includes a \Symfony\Component\Console\Helper\TableCell with colspan=2
        $rowsWithFooter = $rows;
        $rowsWithFooter[] = new \Symfony\Component\Console\Helper\TableSeparator;
        $rowsWithFooter[] = [
            new \Symfony\Component\Console\Helper\TableCell(
                '* source locale',
                ['colspan' => 2]
            ),
        ];

        $table->setHeaders($headers)
            ->setRows($rowsWithFooter)
            ->setStyle('box');

        $table->render();
    }
}
