<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Material;
use App\Models\Shape;
use App\Models\MaterialDimension;

class MaterialDimensionSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('material_dimensions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $materialIds = Material::pluck('id', 'name');
        $shapeIds = Shape::pluck('id', 'name');
        $categoryIds = Category::pluck('id', 'name');

        if (!$materialIds->count() || !$shapeIds->count() || !$categoryIds->count()) {
            echo "Attention: Les seeders de Material, Shape et Category doivent être exécutés en premier.\n";
            return;
        }

        $dimensions = [
            // LAITON - SIGNALÉTIQUE (8)
            ['material' => 'Laiton', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 10.0, 'height' => 20.0, 'price_fixed_xof' => 45000.00],
            ['material' => 'Laiton', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 14.0, 'height' => 20.0, 'price_fixed_xof' => 55000.00],
            ['material' => 'Laiton', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 20.0, 'height' => 30.0, 'price_fixed_xof' => 135000.00],
            ['material' => 'Laiton', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 30.0, 'height' => 30.0, 'price_fixed_xof' => 145000.00],
            ['material' => 'Laiton', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 20.0, 'height' => 40.0, 'price_fixed_xof' => 185000.00],
            ['material' => 'Laiton', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 25.0, 'height' => 25.0, 'price_fixed_xof' => 75000.00],
            ['material' => 'Laiton', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 25.0, 'height' => 30.0, 'price_fixed_xof' => 95000.00],
            ['material' => 'Laiton', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 30.0, 'height' => 40.0, 'price_fixed_xof' => 195000.00],
            
            // ALUMINIUM - SIGNALÉTIQUE (8)
            ['material' => 'Aluminium', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 10.0, 'height' => 20.0, 'price_fixed_xof' => 35000.00],
            ['material' => 'Aluminium', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 14.0, 'height' => 20.0, 'price_fixed_xof' => 45000.00],
            ['material' => 'Aluminium', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 20.0, 'height' => 30.0, 'price_fixed_xof' => 105000.00],
            ['material' => 'Aluminium', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 30.0, 'height' => 30.0, 'price_fixed_xof' => 135000.00],
            ['material' => 'Aluminium', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 20.0, 'height' => 40.0, 'price_fixed_xof' => 135000.00],
            ['material' => 'Aluminium', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 25.0, 'height' => 25.0, 'price_fixed_xof' => 75000.00],
            ['material' => 'Aluminium', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 25.0, 'height' => 30.0, 'price_fixed_xof' => 95000.00],
            ['material' => 'Aluminium', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 30.0, 'height' => 40.0, 'price_fixed_xof' => 155000.00],
            
            // ALUMINIUM COBALT - SIGNALÉTIQUE (8)
            ['material' => 'Aluminium Cobalt', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 10.0, 'height' => 20.0, 'price_fixed_xof' => 45000.00],
            ['material' => 'Aluminium Cobalt', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 14.0, 'height' => 20.0, 'price_fixed_xof' => 55000.00],
            ['material' => 'Aluminium Cobalt', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 20.0, 'height' => 30.0, 'price_fixed_xof' => 135000.00],
            ['material' => 'Aluminium Cobalt', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 30.0, 'height' => 30.0, 'price_fixed_xof' => 180000.00],
            ['material' => 'Aluminium Cobalt', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 20.0, 'height' => 40.0, 'price_fixed_xof' => 180000.00],
            ['material' => 'Aluminium Cobalt', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 25.0, 'height' => 25.0, 'price_fixed_xof' => 90000.00],
            ['material' => 'Aluminium Cobalt', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 25.0, 'height' => 30.0, 'price_fixed_xof' => 135000.00],
            ['material' => 'Aluminium Cobalt', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 30.0, 'height' => 40.0, 'price_fixed_xof' => 180000.00],
            // Pin's en Aluminium Cobalt
            ['material' => 'Aluminium Cobalt', 'shape' => 'Pin\'s / Badge Rond', 'category' => 'Personnel', 'width' => 3.0, 'height' => 10.0, 'price_fixed_xof' => 6500.00], 
            
            // GRANIT - FUNÉRAIRE 
            ['material' => 'Granit', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Funéraire', 'width' => 21.0, 'height' => 29.7, 'price_fixed_xof' => 165000.00],
            ['material' => 'Granit', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Funéraire', 'width' => 30.0, 'height' => 40.0, 'price_fixed_xof' => 275000.00],
            ['material' => 'Granit', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Funéraire', 'width' => 40.0, 'height' => 50.0, 'price_fixed_xof' => 325000.00],
            ['material' => 'Granit', 'shape' => 'Livre (Ouvert)', 'category' => 'Funéraire', 'width' => 30.0, 'height' => 40.0, 'price_fixed_xof' => 185000.00],
            ['material' => 'Granit', 'shape' => 'Livre (Ouvert)', 'category' => 'Funéraire', 'width' => 25.0, 'height' => 25.0, 'price_fixed_xof' => 165000.00],
            ['material' => 'Granit', 'shape' => 'Livre (Ouvert)', 'category' => 'Funéraire', 'width' => 60.0, 'height' => 60.0, 'price_fixed_xof' => 335000.00],
            ['material' => 'Granit', 'shape' => 'Cœur', 'category' => 'Funéraire', 'width' => 50.0, 'height' => 40.0, 'price_fixed_xof' => 295000.00],
            ['material' => 'Granit', 'shape' => 'Cœur', 'category' => 'Funéraire', 'width' => 30.0, 'height' => 40.0, 'price_fixed_xof' => 275000.00],
            ['material' => 'Granit', 'shape' => 'Cœur', 'category' => 'Funéraire', 'width' => 40.0, 'height' => 40.0, 'price_fixed_xof' => 295000.00],
            ['material' => 'Granit', 'shape' => 'Pierre Tombale Classique', 'category' => 'Funéraire', 'width' => 24.0, 'height' => 30.0, 'price_fixed_xof' => 135000.00],
            ['material' => 'Granit', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Funéraire', 'width' => 20.0, 'height' => 40.0, 'price_fixed_xof' => 235000.00], 
            ['material' => 'Granit', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Funéraire', 'width' => 30.0, 'height' => 30.0, 'price_fixed_xof' => 270000.00],
            ['material' => 'Granit', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Funéraire', 'width' => 40.0, 'height' => 40.0, 'price_fixed_xof' => 325000.00], 
            ['material' => 'Granit', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Funéraire', 'width' => 20.0, 'height' => 20.0, 'price_fixed_xof' => 175000.00], 
            
            // ACRYLIQUE (PMMA) - SIGNALÉTIQUE (5)
            ['material' => 'Acrylique (PMMA)', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 5.0, 'height' => 10.0, 'price_fixed_xof' => 8000.00],
            ['material' => 'Acrylique (PMMA)', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 7.0, 'height' => 12.0, 'price_fixed_xof' => 12000.00],
            ['material' => 'Acrylique (PMMA)', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 10.0, 'height' => 10.0, 'price_fixed_xof' => 15000.00],
            ['material' => 'Acrylique (PMMA)', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 15.0, 'height' => 15.0, 'price_fixed_xof' => 25000.00],
            ['material' => 'Acrylique (PMMA)', 'shape' => 'Plaque Rectangle Standard', 'category' => 'Signalétique', 'width' => 15.0, 'height' => 20.0, 'price_fixed_xof' => 35000.00],
        ];

        $dataToInsert = [];
        foreach ($dimensions as $item) {
            
            $dimensionLabel = $item['width'] . 'cm x ' . $item['height'] . 'cm';

            $dataToInsert[] = [
                'material_id' => $materialIds[$item['material']],
                'shape_id' => $shapeIds[$item['shape']],
                'category_id' => $categoryIds[$item['category']],
                'dimension_label' => $dimensionLabel, 
                'unit_price_fcfa' => $item['price_fixed_xof'], 
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('material_dimensions')->insert($dataToInsert);
    }
}
