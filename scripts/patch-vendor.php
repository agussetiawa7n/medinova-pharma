<?php
/**
 * Re-applies vendor patches needed for OneDrive / Windows ReparsePoint compatibility.
 * PHP's is_writable() always returns false on OneDrive-synced directories (ReparsePoints),
 * even when the directory is actually writable. These patches fall back to a real write test.
 *
 * Run via: php scripts/patch-vendor.php
 * Auto-runs after: composer install / composer update (via post-autoload-dump)
 */

$patches = [
    __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Foundation/PackageManifest.php' => [
        'search'  => "        if (! is_writable(\$dirname = dirname(\$this->manifestPath))) {\n            throw new Exception(\"The {\$dirname} directory must be present and writable.\");\n        }",
        'replace' => "        \$dirname = dirname(\$this->manifestPath);\n        \$canWrite = is_writable(\$dirname) || (is_dir(\$dirname) && @file_put_contents(\$dirname.DIRECTORY_SEPARATOR.'.writable_test', '') !== false && @unlink(\$dirname.DIRECTORY_SEPARATOR.'.writable_test'));\n        if (! \$canWrite) {\n            throw new Exception(\"The {\$dirname} directory must be present and writable.\");\n        }",
    ],
    __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Foundation/ProviderRepository.php' => [
        'search'  => "        if (! is_writable(\$dirname = dirname(\$this->manifestPath))) {\n            throw new Exception(\"The {\$dirname} directory must be present and writable.\");\n        }",
        'replace' => "        \$dirname = dirname(\$this->manifestPath);\n        \$canWrite = is_writable(\$dirname) || (is_dir(\$dirname) && @file_put_contents(\$dirname.DIRECTORY_SEPARATOR.'.writable_test', '') !== false && @unlink(\$dirname.DIRECTORY_SEPARATOR.'.writable_test'));\n        if (! \$canWrite) {\n            throw new Exception(\"The {\$dirname} directory must be present and writable.\");\n        }",
    ],
];

foreach ($patches as $file => $patch) {
    $path = realpath($file);
    if (!$path || !file_exists($path)) {
        echo "SKIP (not found): $file\n";
        continue;
    }
    $contents = file_get_contents($path);
    if (str_contains($contents, $patch['search'])) {
        $patched = str_replace($patch['search'], $patch['replace'], $contents);
        file_put_contents($path, $patched);
        echo "PATCHED: $file\n";
    } elseif (str_contains($contents, '.writable_test')) {
        echo "ALREADY PATCHED: $file\n";
    } else {
        echo "WARNING: Pattern not found in $file — may need manual review.\n";
    }
}

echo "Done.\n";
