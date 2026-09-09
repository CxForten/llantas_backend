<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $business = Business::create([
        'name'    => 'Motor Planet',
        'ruc'     => '',
        'address' => '',
        'phone'   => '',
        'email'   => '',
    ]);

    User::create([
        'business_id' => $business->id,
        'name'        => 'Administrador',
        'email'       => 'admin@llanterapro.local',
        'password'    => Hash::make('admin123'),
    ]);
}
}
