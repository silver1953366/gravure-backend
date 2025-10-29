<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Discount;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Discount::create([
            'name' => 'Rabais Volume 10%',
            'code' => 'VOL10K', // <--- AJOUTÉ
            'type' => 'percentage',
            'value' => 10.00,
            'min_order_amount' => 50000.00,
            'is_active' => true,
        ]);

        Discount::create([
            'name' => 'Rabais Fixe 5000',
            'code' => 'FIXE5M', // <--- AJOUTÉ
            'type' => 'fixed',
            'value' => 5000.00,
            'min_order_amount' => 100000.00,
            'is_active' => true,
        ]);
        
        Discount::create([
            'name' => 'Ancien Client (Inactif)',
            'code' => 'OLDCLI', // <--- AJOUTÉ
            'type' => 'percentage',
            'value' => 5.00,
            'min_order_amount' => 0.00,
            'is_active' => false,
        ]);
    }
}