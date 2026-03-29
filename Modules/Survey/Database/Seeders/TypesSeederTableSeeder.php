<?php

namespace Modules\Survey\Database\Seeders;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class TypesSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = ['text', 'radio', 'checkbox', 'rating'];

        foreach ($types as $type) {
            DB::table('types')->insert([
                'type_name' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
