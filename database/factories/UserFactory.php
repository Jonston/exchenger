<?php

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'gnr' => $faker->randomFloat(null, 100, 100),
        'stb' => $faker->randomFloat(null, 100, 100)
    ];
});
