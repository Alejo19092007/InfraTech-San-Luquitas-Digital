<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos ficticios de ejemplo. Se ejecuta en cada arranque del contenedor
     * (entrypoint), pero cada seeder solo inserta si su tabla está vacía.
     * No usa Faker: en producción no se instalan dependencias de desarrollo.
     */
    public function run(): void
    {
        $this->call([
            NovedadSeeder::class,
            RecursoSeeder::class,
        ]);
    }
}
