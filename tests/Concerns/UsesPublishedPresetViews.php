<?php

declare(strict_types=1);

namespace Tests\Concerns;

use DOMXPath;
use DOMDocument;
use Illuminate\Support\Facades\File;
use Simtabi\Laranail\AuthKit\Preset\Providers\PresetServiceProvider;

trait UsesPublishedPresetViews
{
    protected function publishPresetViews(): void
    {
        $directory = resource_path('views/vendor/laranail/authkit-preset');
        File::deleteDirectory($directory);
        $this->artisan('vendor:publish', ['--tag' => 'laranail::authkit-preset-views'])->assertSuccessful();

        // Publishing occurs after the test application's boot. A normal next request boots with
        // this override directory present; refresh the same namespace hints here to represent it.
        $finder = app('view')->getFinder();
        foreach ([PresetServiceProvider::VIEW_NAMESPACE, PresetServiceProvider::COMPONENT_NAMESPACE] as $namespace) {
            $finder->prependNamespace($namespace, $directory);
        }
        $finder->flush();
        expect($finder->find('laranail/authkit-preset::blade.login'))->toBe($directory . '/blade/login.blade.php')
            ->and($finder->find('laranail-authkit-preset::components.input'))->toBe($directory . '/components/input.blade.php');
    }

    protected function removePublishedPresetViews(): void
    {
        File::deleteDirectory(resource_path('views/vendor/laranail/authkit-preset'));
    }

    /** Read the actual form action, method and input names instead of bypassing the form. */
    protected function presetForm(string $html, string $input): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument;
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $form = $xpath->query('//form[.//input[@name="' . $input . '"]]')->item(0);
        expect($form)->not->toBeNull('No rendered form contains ' . $input);
        $fields = [];
        foreach ($xpath->query('.//input[@name]', $form) as $field) {
            $fields[$field->getAttribute('name')] = $field->getAttribute('value');
        }

        return [
            'action' => $form->getAttribute('action'),
            'method' => strtolower($fields['_method'] ?? $form->getAttribute('method')),
            'fields' => $fields,
        ];
    }
}
