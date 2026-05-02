<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBrands();
        $this->seedCategories();
        $this->seedProducts();
        $this->seedBanners();
        $this->seedCoupons();
        $this->seedPages();
        $this->seedSettings();
    }

    private function seedSettings(): void
    {
        $settings = [
            ['contact.phone',        '+91 000000 00000',                 'contact', 'string'],
            ['contact.email',        'support@medinovapharma.com',      'contact', 'string'],
            ['contact.address',      'Jariptaka, Nagpur, Maharashtra', 'contact', 'string'],
            ['contact.hours',        'Mon-Sun: 8:00 AM - 11:00 PM',     'contact', 'string'],
            ['social.facebook',      'https://facebook.com/medinovapharma', 'social', 'string'],
            ['social.twitter',       'https://twitter.com/medinovapharma',  'social', 'string'],
            ['social.instagram',     'https://instagram.com/medinovapharma', 'social', 'string'],
            ['social.linkedin',      'https://linkedin.com/company/medinovapharma', 'social', 'string'],
            ['pricing.free_shipping_threshold', '499', 'pricing', 'integer'],
            ['pricing.delivery_fee',    '50',  'pricing', 'integer'],
            ['site.currency_symbol', '₹',   'shop', 'string'],
        ];
        foreach ($settings as [$key, $value, $group, $type]) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'type' => $type]
            );
        }
    }

    // ---------------------------------------------------------------
    // BRANDS
    // ---------------------------------------------------------------
    private function seedBrands(): void
    {
        $brands = [
            ['name' => 'Sun Pharma',      'description' => 'India\'s largest pharmaceutical company.',       'is_active' => true, 'sort_order' => 1],
            ['name' => 'Cipla',           'description' => 'Affordable medicines across therapeutic areas.',  'is_active' => true, 'sort_order' => 2],
            ['name' => 'Dr. Reddy\'s',    'description' => 'Global pharmaceutical company from India.',       'is_active' => true, 'sort_order' => 3],
            ['name' => 'Abbott India',    'description' => 'Healthcare innovations for a healthier world.',   'is_active' => true, 'sort_order' => 4],
            ['name' => 'Mankind Pharma', 'description' => 'Quality healthcare at affordable prices.',        'is_active' => true, 'sort_order' => 5],
            ['name' => 'Himalaya',        'description' => 'Natural wellness products and herbal remedies.',  'is_active' => true, 'sort_order' => 6],
            ['name' => 'Dabur',           'description' => 'Trusted Ayurvedic & natural health products.',   'is_active' => true, 'sort_order' => 7],
            ['name' => 'Pfizer',          'description' => 'Global leader in biopharmaceuticals.',           'is_active' => true, 'sort_order' => 8],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['slug' => Str::slug($brand['name'])],
                $brand + ['slug' => Str::slug($brand['name'])]
            );
        }
    }

    // ---------------------------------------------------------------
    // CATEGORIES
    // ---------------------------------------------------------------
    private function seedCategories(): void
    {
        $categories = [
            [
                'name'        => 'Medicines',
                'icon'        => '💊',
                'description' => 'Prescription and OTC medicines for all health needs.',
                'sort_order'  => 1,
                'children'    => [
                    ['name' => 'Antibiotics',       'icon' => '🦠', 'sort_order' => 1],
                    ['name' => 'Pain Relief',        'icon' => '🩹', 'sort_order' => 2],
                    ['name' => 'Diabetes Care',      'icon' => '🩸', 'sort_order' => 3],
                    ['name' => 'Heart Care',         'icon' => '❤️', 'sort_order' => 4],
                    ['name' => 'Cold & Flu',         'icon' => '🤧', 'sort_order' => 5],
                ],
            ],
            [
                'name'        => 'Vitamins & Supplements',
                'icon'        => '🌿',
                'description' => 'Boost your health with vitamins, minerals & supplements.',
                'sort_order'  => 2,
                'children'    => [
                    ['name' => 'Multivitamins',      'icon' => '💊', 'sort_order' => 1],
                    ['name' => 'Protein Supplements','icon' => '💪', 'sort_order' => 2],
                    ['name' => 'Omega & Fish Oil',   'icon' => '🐟', 'sort_order' => 3],
                    ['name' => 'Calcium & Bone',     'icon' => '🦴', 'sort_order' => 4],
                ],
            ],
            [
                'name'        => 'Personal Care',
                'icon'        => '🧴',
                'description' => 'Skincare, haircare, and personal hygiene products.',
                'sort_order'  => 3,
                'children'    => [
                    ['name' => 'Skin Care',          'icon' => '✨', 'sort_order' => 1],
                    ['name' => 'Hair Care',          'icon' => '💇', 'sort_order' => 2],
                    ['name' => 'Oral Care',          'icon' => '🦷', 'sort_order' => 3],
                    ['name' => 'Eye Care',           'icon' => '👁️', 'sort_order' => 4],
                ],
            ],
            [
                'name'        => 'Baby & Mother Care',
                'icon'        => '👶',
                'description' => 'Safe and trusted products for babies and new mothers.',
                'sort_order'  => 4,
                'children'    => [
                    ['name' => 'Baby Food',          'icon' => '🍼', 'sort_order' => 1],
                    ['name' => 'Baby Skincare',      'icon' => '🧸', 'sort_order' => 2],
                    ['name' => 'Maternity Care',     'icon' => '🤰', 'sort_order' => 3],
                ],
            ],
            [
                'name'        => 'Devices & Equipment',
                'icon'        => '🩺',
                'description' => 'Medical devices for home health monitoring.',
                'sort_order'  => 5,
                'children'    => [
                    ['name' => 'Blood Pressure Monitors', 'icon' => '💓', 'sort_order' => 1],
                    ['name' => 'Glucometers',              'icon' => '🩸', 'sort_order' => 2],
                    ['name' => 'Thermometers',             'icon' => '🌡️', 'sort_order' => 3],
                    ['name' => 'Pulse Oximeters',          'icon' => '📡', 'sort_order' => 4],
                ],
            ],
            [
                'name'        => 'Ayurveda & Herbal',
                'icon'        => '🌱',
                'description' => 'Traditional Ayurvedic medicines and herbal products.',
                'sort_order'  => 6,
                'children'    => [],
            ],
            [
                'name'        => 'Fitness & Nutrition',
                'icon'        => '🏃',
                'description' => 'Sports nutrition and fitness supplements.',
                'sort_order'  => 7,
                'children'    => [],
            ],
            [
                'name'        => 'Sexual Wellness',
                'icon'        => '❤️',
                'description' => 'Products for sexual health and wellness.',
                'sort_order'  => 8,
                'children'    => [],
            ],
        ];

        foreach ($categories as $catData) {
            $children = $catData['children'];
            unset($catData['children']);

            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($catData['name'])],
                $catData + [
                    'slug'        => Str::slug($catData['name']),
                    'is_active'   => true,
                    'show_in_menu'=> true,
                ]
            );

            foreach ($children as $child) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($child['name'])],
                    $child + [
                        'slug'        => Str::slug($child['name']),
                        'parent_id'   => $parent->id,
                        'is_active'   => true,
                        'show_in_menu'=> true,
                    ]
                );
            }
        }
    }

    // ---------------------------------------------------------------
    // PRODUCTS
    // ---------------------------------------------------------------
    private function seedProducts(): void
    {
        $brands     = Brand::pluck('id', 'slug');
        $categories = Category::pluck('id', 'slug');

        $products = [
            // ---- Pain Relief ----
            [
                'name'              => 'Dolo 650 Paracetamol Tablet',
                'category'          => 'pain-relief',
                'brand'             => 'mankind-pharma',
                'sku'               => 'DOLO-650',
                'price'             => 30.00,
                'compare_price'     => 40.00,
                'short_description' => 'Fast-acting paracetamol for fever, headache and body pain relief.',
                'description'       => '<p>Dolo 650 contains Paracetamol 650mg, used for the relief of mild to moderate pain and fever. It is indicated for headache, toothache, back pain, arthritis, menstrual cramps, and cold/flu symptoms.</p><p><strong>Dosage:</strong> 1 tablet 3-4 times daily, or as directed by physician.</p>',
                'composition'       => 'Paracetamol 650 mg',
                'stock_quantity'    => 500,
                'is_featured'       => true,
                'is_best_seller'    => true,
                'is_new_arrival'    => false,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Dolo+650',
            ],
            [
                'name'              => 'Combiflam Ibuprofen + Paracetamol',
                'category'          => 'pain-relief',
                'brand'             => 'abbott-india',
                'sku'               => 'COMBI-400',
                'price'             => 45.00,
                'compare_price'     => 58.00,
                'short_description' => 'Dual action pain & fever relief with Ibuprofen and Paracetamol.',
                'description'       => '<p>Combiflam combines the analgesic and anti-inflammatory properties of Ibuprofen with the antipyretic action of Paracetamol for fast and effective pain relief.</p>',
                'composition'       => 'Ibuprofen 400mg + Paracetamol 325mg',
                'stock_quantity'    => 350,
                'is_featured'       => true,
                'is_best_seller'    => false,
                'is_new_arrival'    => false,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Combiflam',
            ],
            // ---- Antibiotics ----
            [
                'name'              => 'Azithromycin 500mg Tablet',
                'category'          => 'antibiotics',
                'brand'             => 'cipla',
                'sku'               => 'AZITH-500',
                'price'             => 85.00,
                'compare_price'     => 110.00,
                'short_description' => 'Broad-spectrum antibiotic for bacterial infections.',
                'description'       => '<p>Azithromycin 500mg is a macrolide antibiotic used to treat a wide variety of bacterial infections including respiratory tract infections, skin infections, ear infections, and STDs.</p><p><strong>Note:</strong> Take only as prescribed by your doctor. Complete the full course.</p>',
                'composition'       => 'Azithromycin 500 mg',
                'stock_quantity'    => 200,
                'is_featured'       => true,
                'is_best_seller'    => false,
                'is_new_arrival'    => false,
                'requires_prescription' => true,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Azithromycin',
            ],
            [
                'name'              => 'Amoxicillin 500mg Capsule',
                'category'          => 'antibiotics',
                'brand'             => 'sun-pharma',
                'sku'               => 'AMOX-500',
                'price'             => 65.00,
                'compare_price'     => 80.00,
                'short_description' => 'Penicillin-type antibiotic for bacterial infections.',
                'description'       => '<p>Amoxicillin is a penicillin-type antibiotic used to treat many different types of infections including ear, nose, throat, skin, and urinary tract infections.</p>',
                'composition'       => 'Amoxicillin 500 mg',
                'stock_quantity'    => 300,
                'is_featured'       => false,
                'is_best_seller'    => true,
                'is_new_arrival'    => false,
                'requires_prescription' => true,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Amoxicillin',
            ],
            // ---- Diabetes Care ----
            [
                'name'              => 'Metformin 500mg Tablet',
                'category'          => 'diabetes-care',
                'brand'             => 'sun-pharma',
                'sku'               => 'METF-500',
                'price'             => 55.00,
                'compare_price'     => 70.00,
                'short_description' => 'First-line medication for type 2 diabetes management.',
                'description'       => '<p>Metformin is used to control blood sugar levels in type 2 diabetes. It belongs to a class of drugs known as biguanides and works by decreasing glucose production in the liver and improving insulin sensitivity.</p>',
                'composition'       => 'Metformin Hydrochloride 500 mg',
                'stock_quantity'    => 400,
                'is_featured'       => true,
                'is_best_seller'    => false,
                'is_new_arrival'    => false,
                'requires_prescription' => true,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Metformin',
            ],
            [
                'name'              => 'Glucocheck Blood Glucose Monitor Kit',
                'category'          => 'glucometers',
                'brand'             => 'abbott-india',
                'sku'               => 'GLUCO-KIT',
                'price'             => 1299.00,
                'compare_price'     => 1899.00,
                'short_description' => 'Accurate home blood glucose monitoring device with 25 test strips.',
                'description'       => '<p>The Glucocheck kit provides fast and accurate blood glucose readings in just 5 seconds. Includes glucometer, 25 test strips, lancet device, 10 lancets, and a carry case.</p>',
                'composition'       => 'Blood Glucose Monitor + 25 Test Strips',
                'stock_quantity'    => 80,
                'is_featured'       => true,
                'is_best_seller'    => true,
                'is_new_arrival'    => true,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Glucometer',
            ],
            // ---- Vitamins ----
            [
                'name'              => 'Vitamin D3 60000 IU Capsule (Cholecalciferol)',
                'category'          => 'multivitamins',
                'brand'             => 'sun-pharma',
                'sku'               => 'VITD3-60K',
                'price'             => 120.00,
                'compare_price'     => 160.00,
                'short_description' => 'Weekly dose Vitamin D3 supplement for bone and immune health.',
                'description'       => '<p>Vitamin D3 (Cholecalciferol) 60,000 IU is used for the treatment and prevention of Vitamin D deficiency. Helps in calcium absorption, bone strength, and immune system support.</p>',
                'composition'       => 'Cholecalciferol (Vitamin D3) 60,000 IU',
                'stock_quantity'    => 250,
                'is_featured'       => true,
                'is_best_seller'    => true,
                'is_new_arrival'    => false,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Vitamin+D3',
            ],
            [
                'name'              => 'Neurobion Forte Vitamin B Complex',
                'category'          => 'multivitamins',
                'brand'             => 'abbott-india',
                'sku'               => 'NEURO-FORTE',
                'price'             => 95.00,
                'compare_price'     => 125.00,
                'short_description' => 'B-Complex vitamins for nerve health and energy metabolism.',
                'description'       => '<p>Neurobion Forte contains essential B vitamins (B1, B2, B3, B5, B6, B12) that support nerve function, energy production, and red blood cell formation.</p>',
                'composition'       => 'Vitamin B1, B2, B3, B5, B6, B12',
                'stock_quantity'    => 300,
                'is_featured'       => false,
                'is_best_seller'    => true,
                'is_new_arrival'    => false,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Neurobion',
            ],
            [
                'name'              => 'Himalaya Ashwagandha Tablets',
                'category'          => 'multivitamins',
                'brand'             => 'himalaya',
                'sku'               => 'HIM-ASHWA',
                'price'             => 165.00,
                'compare_price'     => 210.00,
                'short_description' => 'Natural adaptogen for stress relief and vitality.',
                'description'       => '<p>Himalaya Ashwagandha (Withania somnifera) is a natural adaptogen that helps manage stress, improves energy levels, supports cognitive function and enhances physical endurance.</p>',
                'composition'       => 'Ashwagandha (Withania somnifera) extract 250 mg',
                'stock_quantity'    => 180,
                'is_featured'       => true,
                'is_best_seller'    => false,
                'is_new_arrival'    => true,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Ashwagandha',
            ],
            // ---- Personal Care ----
            [
                'name'              => 'Cetaphil Gentle Skin Cleanser 250ml',
                'category'          => 'skin-care',
                'brand'             => 'cipla',
                'sku'               => 'CETAPHIL-250',
                'price'             => 375.00,
                'compare_price'     => 450.00,
                'short_description' => 'Gentle, soap-free cleanser for sensitive and dry skin.',
                'description'       => '<p>Cetaphil Gentle Skin Cleanser is a non-irritating formula that gently removes dirt, makeup, and excess oil while maintaining skin\'s natural pH. Suitable for face and body, even for sensitive skin.</p>',
                'composition'       => 'Purified water, Cetyl Alcohol, Propylene Glycol, Sodium Lauryl Sulfate',
                'stock_quantity'    => 150,
                'is_featured'       => true,
                'is_best_seller'    => true,
                'is_new_arrival'    => false,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Cetaphil',
            ],
            [
                'name'              => 'Himalaya Neem Face Wash 100ml',
                'category'          => 'skin-care',
                'brand'             => 'himalaya',
                'sku'               => 'HIM-NEEM-100',
                'price'             => 99.00,
                'compare_price'     => 130.00,
                'short_description' => 'Purifying neem face wash for acne-prone and oily skin.',
                'description'       => '<p>Himalaya Neem Face Wash contains Neem and Turmeric which have natural antibacterial properties that help prevent acne and pimples, leaving skin clean and fresh.</p>',
                'composition'       => 'Neem (Azadirachta indica) extract, Turmeric (Curcuma longa)',
                'stock_quantity'    => 220,
                'is_featured'       => false,
                'is_best_seller'    => true,
                'is_new_arrival'    => false,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Neem+Facewash',
            ],
            // ---- Cold & Flu ----
            [
                'name'              => 'Sinarest Tablet (Cetirizine + Paracetamol)',
                'category'          => 'cold-flu',
                'brand'             => 'cipla',
                'sku'               => 'SINAREST',
                'price'             => 38.00,
                'compare_price'     => 50.00,
                'short_description' => 'Relieves cold, flu symptoms including runny nose, sneezing and fever.',
                'description'       => '<p>Sinarest tablet is a combination of Cetirizine (antihistamine) and Paracetamol that provides effective relief from common cold and flu symptoms including runny nose, sneezing, watery eyes, sore throat, and fever.</p>',
                'composition'       => 'Cetirizine 5mg + Paracetamol 500mg + Phenylephrine 5mg',
                'stock_quantity'    => 400,
                'is_featured'       => false,
                'is_best_seller'    => true,
                'is_new_arrival'    => false,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Sinarest',
            ],
            // ---- Heart Care ----
            [
                'name'              => 'Atorvastatin 10mg Tablet',
                'category'          => 'heart-care',
                'brand'             => 'sun-pharma',
                'sku'               => 'ATOR-10',
                'price'             => 75.00,
                'compare_price'     => 95.00,
                'short_description' => 'Cholesterol-lowering statin for cardiovascular protection.',
                'description'       => '<p>Atorvastatin (Statin) is used to lower cholesterol and triglycerides in the blood. It helps reduce the risk of heart attack, stroke, and other heart complications in patients with type 2 diabetes, coronary heart disease, or other risk factors.</p>',
                'composition'       => 'Atorvastatin Calcium 10 mg',
                'stock_quantity'    => 260,
                'is_featured'       => true,
                'is_best_seller'    => false,
                'is_new_arrival'    => false,
                'requires_prescription' => true,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Atorvastatin',
            ],
            // ---- BP Monitor ----
            [
                'name'              => 'Omron HEM-7120 Blood Pressure Monitor',
                'category'          => 'blood-pressure-monitors',
                'brand'             => 'abbott-india',
                'sku'               => 'OMRON-7120',
                'price'             => 2499.00,
                'compare_price'     => 3200.00,
                'short_description' => 'Automatic digital BP monitor with memory for 30 readings.',
                'description'       => '<p>The Omron HEM-7120 is a fully automatic upper arm blood pressure monitor with Intellisense technology. It stores up to 30 readings with date and time stamp, making it easy to track your blood pressure trends.</p>',
                'composition'       => 'Digital BP Monitor + Arm Cuff + Batteries + Carrying Case',
                'stock_quantity'    => 55,
                'is_featured'       => true,
                'is_best_seller'    => true,
                'is_new_arrival'    => true,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=BP+Monitor',
            ],
            // ---- Protein ----
            [
                'name'              => 'Whey Protein Concentrate Chocolate 1kg',
                'category'          => 'protein-supplements',
                'brand'             => 'mankind-pharma',
                'sku'               => 'WHEY-CHOCO-1K',
                'price'             => 1299.00,
                'compare_price'     => 1799.00,
                'short_description' => 'High-quality whey protein for muscle recovery and growth.',
                'description'       => '<p>Premium whey protein concentrate with 24g protein per serving. Rich in BCAAs to support muscle recovery, growth, and overall fitness performance. Available in delicious chocolate flavour.</p>',
                'composition'       => 'Whey Protein Concentrate 80%, Cocoa Powder, Natural Flavours',
                'stock_quantity'    => 90,
                'is_featured'       => true,
                'is_best_seller'    => false,
                'is_new_arrival'    => true,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Whey+Protein',
            ],
            // ---- Ayurveda ----
            [
                'name'              => 'Dabur Chyawanprash Special 1kg',
                'category'          => 'ayurveda-herbal',
                'brand'             => 'dabur',
                'sku'               => 'DAB-CHYAWAN-1K',
                'price'             => 325.00,
                'compare_price'     => 400.00,
                'short_description' => 'Classic immunity booster with 41 Ayurvedic herbs including Amla.',
                'description'       => '<p>Dabur Chyawanprash is an Ayurvedic health supplement prepared using 41 natural herbs and Amla (Indian Gooseberry). It helps boost immunity, improves lung function, increases stamina, and promotes overall health.</p>',
                'composition'       => 'Amla (Emblica officinalis), Ashwagandha, Giloy, Shatavari, and 37 other Ayurvedic herbs',
                'stock_quantity'    => 140,
                'is_featured'       => true,
                'is_best_seller'    => true,
                'is_new_arrival'    => false,
                'requires_prescription' => false,
                'thumbnail'         => 'https://placehold.co/400x400/FFF5F7/FA4E67?text=Chyawanprash',
            ],
        ];

        foreach ($products as $productData) {
            $categorySlug = $productData['category'];
            $brandSlug    = $productData['brand'];

            $categoryId = $categories[$categorySlug] ?? $categories->first();
            $brandId    = $brands[$brandSlug] ?? $brands->first();

            unset($productData['category'], $productData['brand']);

            Product::firstOrCreate(
                ['sku' => $productData['sku']],
                $productData + [
                    'category_id'     => $categoryId,
                    'brand_id'        => $brandId,
                    'slug'            => Str::slug($productData['name']),
                    'is_active'       => true,
                    'track_inventory' => true,
                    'unit'            => 'piece',
                    'sort_order'      => 0,
                ]
            );
        }
    }

    // ---------------------------------------------------------------
    // BANNERS
    // ---------------------------------------------------------------
    private function seedBanners(): void
    {
        $heroBanners = [
            [
                'title'       => 'Your Health, Our Priority',
                'subtitle'    => 'Shop 50,000+ medicines, vitamins & health products with home delivery. Use code FIRST10 for 10% off your first order.',
                'badge_text'  => '✓ Genuine Medicines',
                'button_text' => 'Shop Now',
                'button_url'  => '/products',
                'image'       => 'https://placehold.co/1400x500/FFF0F3/FA4E67?text=MediNova+Hero+Banner+1',
                'position'    => 'hero',
                'sort_order'  => 1,
                'is_active'   => true,
            ],
            [
                'title'       => 'Trusted Medicines, Delivered Fast',
                'subtitle'    => 'Get medicines at your doorstep within 4 hours. 100% authentic products from licensed pharmacies.',
                'badge_text'  => '🚀 Express Delivery',
                'button_text' => 'Explore Products',
                'button_url'  => '/products',
                'image'       => 'https://placehold.co/1400x500/FFF0F3/FF647B?text=MediNova+Hero+Banner+2',
                'position'    => 'hero',
                'sort_order'  => 2,
                'is_active'   => true,
            ],
            [
                'title'       => 'Upload Prescription & Order',
                'subtitle'    => 'Have a prescription? Upload it easily and get your medicines delivered hassle-free at the best prices.',
                'badge_text'  => '🔒 100% Secure',
                'button_text' => 'Upload Prescription',
                'button_url'  => '/prescriptions',
                'image'       => 'https://placehold.co/1400x500/FFF0F3/FF879A?text=MediNova+Hero+Banner+3',
                'position'    => 'hero',
                'sort_order'  => 3,
                'is_active'   => true,
            ],
        ];

        $promoBanners = [
            [
                'title'       => 'Vitamins & Supplements Sale',
                'subtitle'    => 'Up to 40% off on all vitamins.',
                'badge_text'  => 'UP TO 40% OFF',
                'button_text' => 'Shop Vitamins',
                'button_url'  => '/products?category=vitamins-supplements',
                'image'       => 'https://placehold.co/600x250/FFF0F3/FA4E67?text=Vitamins+Sale',
                'position'    => 'promo',
                'sort_order'  => 1,
                'is_active'   => true,
            ],
            [
                'title'       => 'Diabetes Care Bundle',
                'subtitle'    => 'Glucometer + 50 strips at special price.',
                'badge_text'  => 'SAVE ₹600',
                'button_text' => 'View Bundle',
                'button_url'  => '/products?category=diabetes-care',
                'image'       => 'https://placehold.co/600x250/FFF0F3/FF647B?text=Diabetes+Bundle',
                'position'    => 'promo',
                'sort_order'  => 2,
                'is_active'   => true,
            ],
            [
                'title'       => 'New Arrivals in Skincare',
                'subtitle'    => 'Explore latest dermatologist-approved products.',
                'badge_text'  => 'NEW ARRIVALS',
                'button_text' => 'Discover Now',
                'button_url'  => '/products?category=skin-care',
                'image'       => 'https://placehold.co/600x250/FFF0F3/FF879A?text=Skincare+New',
                'position'    => 'promo',
                'sort_order'  => 3,
                'is_active'   => true,
            ],
        ];

        foreach (array_merge($heroBanners, $promoBanners) as $banner) {
            Banner::firstOrCreate(
                ['title' => $banner['title'], 'position' => $banner['position']],
                $banner
            );
        }
    }

    // ---------------------------------------------------------------
    // COUPONS
    // ---------------------------------------------------------------
    private function seedCoupons(): void
    {
        $coupons = [
            [
                'code'                  => 'FIRST10',
                'description'           => '10% off on your first order (max ₹200 discount)',
                'type'                  => 'percentage',
                'value'                 => 10,
                'min_order_amount'      => 299,
                'max_discount_amount'   => 200,
                'usage_limit'           => 1000,
                'usage_limit_per_user'  => 1,
                'is_active'             => true,
                'starts_at'             => now(),
                'expires_at'            => now()->addYear(),
            ],
            [
                'code'                  => 'SAVE50',
                'description'           => 'Flat ₹50 off on orders above ₹499',
                'type'                  => 'fixed',
                'value'                 => 50,
                'min_order_amount'      => 499,
                'max_discount_amount'   => 50,
                'usage_limit'           => 5000,
                'usage_limit_per_user'  => 3,
                'is_active'             => true,
                'starts_at'             => now(),
                'expires_at'            => now()->addMonths(6),
            ],
            [
                'code'                  => 'HEALTH20',
                'description'           => '20% off on vitamins and supplements (max ₹300)',
                'type'                  => 'percentage',
                'value'                 => 20,
                'min_order_amount'      => 599,
                'max_discount_amount'   => 300,
                'usage_limit'           => 2000,
                'usage_limit_per_user'  => 2,
                'is_active'             => true,
                'starts_at'             => now(),
                'expires_at'            => now()->addMonths(3),
            ],
            [
                'code'                  => 'MEDIFREE',
                'description'           => 'Free shipping on orders above ₹199 (₹50 shipping discount)',
                'type'                  => 'fixed',
                'value'                 => 50,
                'min_order_amount'      => 199,
                'max_discount_amount'   => 50,
                'usage_limit'           => 99999,
                'usage_limit_per_user'  => 10,
                'is_active'             => true,
                'starts_at'             => now(),
                'expires_at'            => now()->addYear(),
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::firstOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }

    // ---------------------------------------------------------------
    // PAGES (CMS)
    // ---------------------------------------------------------------
    private function seedPages(): void
    {
        $pages = [
            [
                'title'            => 'About Us',
                'slug'             => 'about-us',
                'meta_description' => 'Learn about MediNova Pharma — your trusted online pharmacy.',
                'is_active'        => true,
                'content'          => '<h2>About MediNova Pharma</h2>
<p>MediNova Pharma is India\'s most trusted online pharmacy, delivering genuine medicines and healthcare products to your doorstep since 2020. We are committed to making quality healthcare accessible and affordable for everyone.</p>

<h3>Our Mission</h3>
<p>To provide every Indian access to authentic, affordable medicines with the convenience of doorstep delivery and expert pharmacist support.</p>

<h3>Why Choose MediNova?</h3>
<ul>
<li>✅ 100% Genuine Medicines — sourced directly from licensed manufacturers</li>
<li>✅ Licensed Pharmacy — registered with state drug regulatory authorities</li>
<li>✅ Expert Pharmacists — available 24/7 for medication queries</li>
<li>✅ Fast Delivery — 4-hour express delivery available in major cities</li>
<li>✅ Easy Returns — 7-day hassle-free return policy</li>
<li>✅ Secure Payments — 256-bit SSL encryption on all transactions</li>
</ul>

<h3>Our Numbers</h3>
<ul>
<li>50,000+ products across 500+ categories</li>
<li>5 million+ satisfied customers</li>
<li>500+ cities served across India</li>
<li>24/7 customer support</li>
</ul>',
            ],
            [
                'title'            => 'Privacy Policy',
                'slug'             => 'privacy-policy',
                'meta_description' => 'MediNova Pharma Privacy Policy — how we collect and protect your data.',
                'is_active'        => true,
                'content'          => '<h2>Privacy Policy</h2>
<p><em>Last updated: January 2025</em></p>

<p>At MediNova Pharma, we are committed to protecting your privacy and personal information. This policy explains how we collect, use, and safeguard your data.</p>

<h3>Information We Collect</h3>
<ul>
<li><strong>Account Information:</strong> Name, email, phone, date of birth</li>
<li><strong>Order Information:</strong> Delivery address, payment method (not card numbers)</li>
<li><strong>Health Information:</strong> Prescriptions uploaded for medicine orders</li>
<li><strong>Usage Data:</strong> Pages visited, products viewed, search queries</li>
</ul>

<h3>How We Use Your Information</h3>
<ul>
<li>Processing and delivering your orders</li>
<li>Sending order confirmations and delivery updates</li>
<li>Providing personalized product recommendations</li>
<li>Improving our services and website</li>
<li>Complying with legal and regulatory requirements</li>
</ul>

<h3>Data Security</h3>
<p>We use industry-standard 256-bit SSL encryption for all data transmission. Your prescription data is stored securely and never shared without consent.</p>

<h3>Your Rights</h3>
<p>You have the right to access, update, or delete your personal data at any time. Contact us at privacy@medinovapharma.com for data requests.</p>',
            ],
            [
                'title'            => 'Terms & Conditions',
                'slug'             => 'terms-conditions',
                'meta_description' => 'MediNova Pharma Terms and Conditions of service.',
                'is_active'        => true,
                'content'          => '<h2>Terms & Conditions</h2>
<p><em>Last updated: January 2025</em></p>

<h3>1. Acceptance of Terms</h3>
<p>By accessing and using MediNova Pharma, you agree to be bound by these Terms and Conditions. If you do not agree, please do not use our services.</p>

<h3>2. Prescription Medicines</h3>
<p>Prescription medicines can only be purchased with a valid prescription from a registered medical practitioner. Submitting false prescriptions is illegal and will result in account termination and legal action.</p>

<h3>3. Product Authenticity</h3>
<p>All products sold on MediNova Pharma are sourced from licensed manufacturers and distributors. We guarantee the authenticity of every product sold on our platform.</p>

<h3>4. Pricing & Payment</h3>
<p>Prices are inclusive of GST unless stated otherwise. We reserve the right to modify prices without prior notice. Payment must be completed before order processing.</p>

<h3>5. Returns & Refunds</h3>
<p>We accept returns within 7 days of delivery for eligible products. Prescription medicines, opened products, and temperature-sensitive items are non-returnable.</p>

<h3>6. Limitation of Liability</h3>
<p>MediNova Pharma is not liable for adverse reactions to medicines used without proper medical consultation. Always consult a healthcare professional before starting any medication.</p>',
            ],
            [
                'title'            => 'Shipping Policy',
                'slug'             => 'shipping-policy',
                'meta_description' => 'Learn about MediNova Pharma shipping rates, delivery timelines, and areas served.',
                'is_active'        => true,
                'content'          => '<h2>Shipping Policy</h2>

<h3>Delivery Charges</h3>
<ul>
<li>Orders above ₹499 — FREE delivery</li>
<li>Orders below ₹499 — ₹50 delivery charge</li>
<li>Express 4-hour delivery — ₹99 (available in select cities)</li>
</ul>

<h3>Delivery Timeline</h3>
<ul>
<li><strong>Metro Cities</strong> (Delhi, Mumbai, Bangalore, Chennai, Hyderabad, Kolkata): 1-2 business days</li>
<li><strong>Tier 2 Cities:</strong> 2-4 business days</li>
<li><strong>Other Locations:</strong> 4-7 business days</li>
</ul>

<h3>Order Tracking</h3>
<p>Once your order is shipped, you will receive an SMS and email with the tracking number and courier details. Track your order anytime from your account dashboard.</p>

<h3>Prescription Orders</h3>
<p>Orders containing prescription medicines will only be dispatched after our pharmacist verifies your prescription. This typically takes 2-4 hours during business hours.</p>',
            ],
            [
                'title'            => 'Return & Refund Policy',
                'slug'             => 'return-refund-policy',
                'meta_description' => 'MediNova Pharma return and refund policy.',
                'is_active'        => true,
                'content'          => '<h2>Return & Refund Policy</h2>

<h3>Eligible for Return</h3>
<ul>
<li>Wrong product delivered</li>
<li>Damaged or defective product</li>
<li>Expired product delivered</li>
<li>Sealed/unopened OTC products (within 7 days)</li>
</ul>

<h3>Not Eligible for Return</h3>
<ul>
<li>Prescription medicines (once dispensed)</li>
<li>Opened or used products</li>
<li>Temperature-sensitive products</li>
<li>Products without original packaging</li>
<li>Products returned after 7 days</li>
</ul>

<h3>Refund Process</h3>
<p>Approved refunds are processed within 5-7 business days to the original payment method. For COD orders, refunds are credited to your MediNova Wallet.</p>

<h3>How to Return</h3>
<ol>
<li>Go to My Orders in your account</li>
<li>Select the order and click "Return/Replace"</li>
<li>Choose return reason and upload photos if applicable</li>
<li>Schedule a pickup (we\'ll arrange free pickup)</li>
</ol>',
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
