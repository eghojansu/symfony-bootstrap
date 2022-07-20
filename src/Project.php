<?php

namespace App;

class Project implements \JsonSerializable
{
    private $name = 'MyApp';
    private $alias = 'MyApp';
    private $description = 'MyApp';
    private $year = 2022;
    private $owner = 'My Company, Inc';
    private $homepage = 'http://mycompany.com';

    public function jsonSerialize(): mixed
    {
        return array(
            'name' => $this->getName(),
            'alias' => $this->getAlias(),
            'desc' => $this->getDescription(),
            'owner' => $this->getOwner(),
            'year' => $this->getYear(),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getHomepage(): string
    {
        return $this->homepage;
    }
}