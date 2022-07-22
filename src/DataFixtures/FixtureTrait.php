<?php

namespace App\DataFixtures;

use Symfony\Component\Yaml\Yaml;

trait FixtureTrait
{
    private static function getData(string $name): array
    {
        return Yaml::parseFile(__DIR__ . '/data/' . $name . '.yml') ?? array();
    }
}