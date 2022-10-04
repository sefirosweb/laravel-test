<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;

class GeneralHelperTestController extends Controller
{
    public function view()
    {
        return view('general_helper');
    }

    public function download_csv()
    {
        $data = $this->generate_data();
        return saveCsvInServerAndDownload($data, 'Test File');
    }

    public function generate_data()
    {
        $faker = \Faker\Factory::create();
        $headers = $faker->words($faker->numberBetween(3, 10));

        $array = [];

        for ($i = 0; $i <= $faker->numberBetween(10, 300); $i++) {
            $data = [];
            foreach ($headers as $header) {
                $data[$header] = $faker->text(20);
            }

            $array[] = $data;
        }

        return $array;
    }
}
