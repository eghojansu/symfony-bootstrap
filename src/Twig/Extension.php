<?php

namespace App\Twig;

use Twig\TwigTest;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Extension\Utils;
use Twig\Extension\AbstractExtension;

final class Extension extends AbstractExtension
{
    public function getFunctions()
    {
        return array(
            new TwigFunction('with_props', array($this, 'withProps'), array('is_safe' => array('html'))),
            new TwigFunction('with_class', array($this, 'withClass'), array('is_safe' => array('html'))),
        );
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('caseTitle', array(Utils::class, 'caseTitle')),
            new TwigFilter('caseKebab', array(Utils::class, 'caseKebab')),
            new TwigFilter('caseSnake', array(Utils::class, 'caseSnake')),
            new TwigFilter('caseCamel', array(Utils::class, 'caseCamel')),
            new TwigFilter('tableFormat', array($this, 'tableFormat')),
        );
    }

    public function getTests()
    {
        return array(
            new TwigTest('numeric', static fn($value) => is_numeric($value)),
            new TwigTest('scalar', static fn($value) => is_scalar($value)),
        );
    }

    public function tableFormat($value, string $format = null): string|int|float|null
    {
        return match (true) {
            $value instanceof \DateTimeInterface => match ($format) {
                'date' => $value->format('d M Y'),
                default => $value->format($format ?? 'd F Y H:i:s'),
            },
            default => $value,
        };
    }

    public function withProps(array|null ...$props): string
    {
        $line = '';
        $flatten = array_reduce(
            $props,
            static function (array|null $flatten, array|null $group) {
                if (isset($flatten['class']) && isset($group['class'])) {
                    $group['class'] = array_merge(Utils::split($flatten['class']), Utils::split($group['class']));
                }

                return array_merge($flatten ?? array(), $group ?? array());
            },
        );

        array_walk($flatten, function ($value, string|int $prop) use (&$line) {
            if (is_numeric($prop)) {
                $line .= ' ' . $this->buildProps($value);
            } elseif (true === $value) {
                $line .= ' ' . $prop;
            } elseif ($value) {
                $line .= ' ' . $prop . '="' . $this->buildProps($value, $prop) . '"';
            }

            return $line;
        });

        return trim($line);
    }

    public function withClass(array|string|null ...$classes): string
    {
        return $this->withProps(...array_map(static fn ($class) => compact('class'), $classes));
    }

    private function buildProps(array|string $value, string $prop = null): string
    {
        return match($prop) {
            'class' => $this->buildClassProp($value),
            default => $this->buildDefaultProp($value),
        };
    }

    private function buildClassProp(array|string $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        $line = '';

        foreach ($value as $class => $expr) {
            if (is_numeric($class)) {
                $class = $expr;
                $expr = true;
            }

            if ($class && $expr) {
                $line .= ' ' . $class;
            }
        }

        return trim($line);
    }

    private function buildDefaultProp(array|string $value): string
    {
        return (string) $value;
    }
}