<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use File;

class BackupRestoreService
{
    /**
     * Export products, categories, brands, and variants into a ZIP archive along with their images.
     *
     * @return string The path to the generated ZIP file
     * @throws \Exception
     */
    public function export(): string
    {
        // 1. Gather database records
        $brands = Brand::all()->toArray();
        $categories = Category::all()->toArray();
        $products = Product::with('variants')->get()->toArray();

        // 2. Prepare manifest
        $manifest = [
            'version' => '1.0.0',
            'exported_at' => now()->toIso8601String(),
            'counts' => [
                'brands' => count($brands),
                'categories' => count($categories),
                'products' => count($products),
            ],
        ];

        $data = [
            'brands' => $brands,
            'categories' => $categories,
            'products' => $products,
        ];

        // 3. Create a temporary ZIP file
        $tempDir = storage_path('app/backup-temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipFileName = 'backup_' . date('Ymd_His') . '.zip';
        $zipPath = $tempDir . '/' . $zipFileName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Could not create ZIP archive at: {$zipPath}");
        }

        // 4. Add database records JSON and Manifest
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString('data.json', json_encode($data, JSON_PRETTY_PRINT));

        // 5. Gather and add images
        $imageDisk = Storage::disk('public');
        $imagesAdded = [];

        // Helper to add image if it exists
        $addImageToZip = function ($path) use ($imageDisk, &$zip, &$imagesAdded) {
            if (empty($path)) {
                return;
            }

            // Skip absolute URLs
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return;
            }

            // Prevent adding duplicates
            if (in_array($path, $imagesAdded)) {
                return;
            }

            if ($imageDisk->exists($path)) {
                $fileContent = $imageDisk->get($path);
                // Store in zip under images/ folder preserving the path
                $zip->addFromString('images/' . $path, $fileContent);
                $imagesAdded[] = $path;
            }
        };

        // Add Brand Logos
        foreach ($brands as $brand) {
            if (!empty($brand['logo'])) {
                $addImageToZip($brand['logo']);
            }
        }

        // Add Category Images and Icons
        foreach ($categories as $category) {
            if (!empty($category['image'])) {
                $addImageToZip($category['image']);
            }
            if (!empty($category['icon'])) {
                $addImageToZip($category['icon']);
            }
        }

        // Add Product Thumbnails and Extra Images
        foreach ($products as $product) {
            if (!empty($product['thumbnail'])) {
                $addImageToZip($product['thumbnail']);
            }

            if (!empty($product['images']) && is_array($product['images'])) {
                foreach ($product['images'] as $image) {
                    $addImageToZip($image);
                }
            } elseif (!empty($product['images']) && is_string($product['images'])) {
                $decoded = json_decode($product['images'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $image) {
                        $addImageToZip($image);
                    }
                }
            }
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Import/Restore products, categories, brands, and variants from a ZIP archive.
     *
     * @param string $zipFilePath Path to the uploaded ZIP file
     * @return array Summary of import operations
     * @throws \Exception
     */
    public function import(string $zipFilePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new \Exception("Could not open ZIP file for restore.");
        }

        // 1. Create extraction directory
        $extractPath = storage_path('app/restore-temp-' . uniqid());
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        // 2. Extract contents
        $zip->extractTo($extractPath);
        $zip->close();

        $dataFile = $extractPath . '/data.json';
        if (!file_exists($dataFile)) {
            File::deleteDirectory($extractPath);
            throw new \Exception("Invalid backup archive: data.json not found.");
        }

        $data = json_decode(file_get_contents($dataFile), true);
        if (!$data) {
            File::deleteDirectory($extractPath);
            throw new \Exception("Invalid backup archive: data.json could not be parsed.");
        }

        // 3. Move images to the public storage disk
        $imageDisk = Storage::disk('public');
        $extractedImagesPath = $extractPath . '/images';
        $imagesRestoredCount = 0;

        if (file_exists($extractedImagesPath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractedImagesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $normExtractedPath = str_replace('\\', '/', $extractedImagesPath);
                    $normRealPath = str_replace('\\', '/', $file->getRealPath());
                    $relativePath = str_replace($normExtractedPath . '/', '', $normRealPath);
                    
                    // Copy file to disk
                    $imageDisk->put($relativePath, file_get_contents($file->getRealPath()));
                    $imagesRestoredCount++;
                }
            }
        }

        // 4. Import/Restore Records within Database Transaction
        $stats = [
            'brands' => ['created' => 0, 'updated' => 0],
            'categories' => ['created' => 0, 'updated' => 0],
            'products' => ['created' => 0, 'updated' => 0],
            'variants' => ['created' => 0, 'updated' => 0, 'deleted' => 0],
            'images_restored' => $imagesRestoredCount,
        ];

        DB::beginTransaction();
        try {
            // Mapping tables to map old IDs to newly generated/existing live IDs
            $brandIdMap = [];
            $categoryIdMap = [];
            $productIdMap = [];

            // A. Import Brands
            $brandsData = $data['brands'] ?? [];
            foreach ($brandsData as $brand) {
                $oldId = $brand['id'];
                
                // Match by unique slug
                $liveBrand = Brand::where('slug', $brand['slug'])->first();
                
                $attributes = $this->filterFields($brand, ['id', 'created_at', 'updated_at']);
                
                if ($liveBrand) {
                    $liveBrand->update($attributes);
                    $stats['brands']['updated']++;
                    $brandIdMap[$oldId] = $liveBrand->id;
                } else {
                    $newBrand = Brand::create($attributes);
                    $stats['brands']['created']++;
                    $brandIdMap[$oldId] = $newBrand->id;
                }
            }

            // B. Import Categories (Pass 1 - Upsert categories without parent links)
            $categoriesData = $data['categories'] ?? [];
            foreach ($categoriesData as $category) {
                $oldId = $category['id'];
                
                // Match by unique slug
                $liveCategory = Category::where('slug', $category['slug'])->first();
                
                $attributes = $this->filterFields($category, ['id', 'parent_id', 'created_at', 'updated_at']);
                $attributes['parent_id'] = null; // Set to null temporarily to prevent foreign key errors

                if ($liveCategory) {
                    $liveCategory->update($attributes);
                    $stats['categories']['updated']++;
                    $categoryIdMap[$oldId] = $liveCategory->id;
                } else {
                    $newCategory = Category::create($attributes);
                    $stats['categories']['created']++;
                    $categoryIdMap[$oldId] = $newCategory->id;
                }
            }

            // C. Import Categories (Pass 2 - Establish parent-child relations)
            foreach ($categoriesData as $category) {
                $oldId = $category['id'];
                $oldParentId = $category['parent_id'];
                
                if (!empty($oldParentId) && isset($categoryIdMap[$oldParentId])) {
                    $liveId = $categoryIdMap[$oldId];
                    $newParentId = $categoryIdMap[$oldParentId];
                    
                    Category::where('id', $liveId)->update(['parent_id' => $newParentId]);
                }
            }

            // D. Import Products
            $productsData = $data['products'] ?? [];
            foreach ($productsData as $product) {
                $oldId = $product['id'];
                
                // Match by unique slug
                $liveProduct = Product::withTrashed()->where('slug', $product['slug'])->first();
                
                $attributes = $this->filterFields($product, ['id', 'category_id', 'brand_id', 'variants', 'created_at', 'updated_at', 'deleted_at']);
                
                // Map Category ID
                $oldCategoryId = $product['category_id'];
                $attributes['category_id'] = ($oldCategoryId && isset($categoryIdMap[$oldCategoryId])) 
                    ? $categoryIdMap[$oldCategoryId] 
                    : null;

                // Map Brand ID
                $oldBrandId = $product['brand_id'];
                $attributes['brand_id'] = ($oldBrandId && isset($brandIdMap[$oldBrandId])) 
                    ? $brandIdMap[$oldBrandId] 
                    : null;

                if ($liveProduct) {
                    // Check if soft deleted on live and restore if needed
                    if ($liveProduct->trashed()) {
                        $liveProduct->restore();
                    }
                    $liveProduct->update($attributes);
                    $stats['products']['updated']++;
                    $productIdMap[$oldId] = $liveProduct->id;
                    $currentProduct = $liveProduct;
                } else {
                    $newProduct = Product::create($attributes);
                    $stats['products']['created']++;
                    $productIdMap[$oldId] = $newProduct->id;
                    $currentProduct = $newProduct;
                }

                // E. Import Product Variants for this product
                $variantsData = $product['variants'] ?? [];
                $processedVariantIds = [];

                foreach ($variantsData as $variant) {
                    // Match existing variant under this product by SKU (if present) or Name
                    $query = ProductVariant::where('product_id', $currentProduct->id);
                    if (!empty($variant['sku'])) {
                        $query->where('sku', $variant['sku']);
                    } else {
                        $query->where('name', $variant['name']);
                    }
                    
                    $liveVariant = $query->first();
                    $variantAttributes = $this->filterFields($variant, ['id', 'product_id', 'created_at', 'updated_at']);
                    $variantAttributes['product_id'] = $currentProduct->id;

                    if ($liveVariant) {
                        $liveVariant->update($variantAttributes);
                        $stats['variants']['updated']++;
                        $processedVariantIds[] = $liveVariant->id;
                    } else {
                        $newVariant = ProductVariant::create($variantAttributes);
                        $stats['variants']['created']++;
                        $processedVariantIds[] = $newVariant->id;
                    }
                }

                // Delete variants of this product that are no longer in the backup, but ONLY if they are not referenced by orders
                $oldVariants = ProductVariant::where('product_id', $currentProduct->id)
                    ->whereNotIn('id', $processedVariantIds)
                    ->get();

                foreach ($oldVariants as $oldVariant) {
                    $isReferenced = OrderItem::where('product_variant_id', $oldVariant->id)->exists();
                    if (!$isReferenced) {
                        $oldVariant->delete();
                        $stats['variants']['deleted']++;
                    } else {
                        Log::warning("Variant ID {$oldVariant->id} ({$oldVariant->name}) cannot be deleted because it is referenced in an OrderItem.");
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            File::deleteDirectory($extractPath);
            throw $e;
        }

        // Clean up extracted files
        File::deleteDirectory($extractPath);

        return $stats;
    }

    /**
     * Helper to filter out system fields from array.
     */
    private function filterFields(array $data, array $exclude): array
    {
        return array_filter($data, function ($key) use ($exclude) {
            return !in_array($key, $exclude);
        }, ARRAY_FILTER_USE_KEY);
    }
}
