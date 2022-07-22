<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Choice extends Constraint
{
    public function __construct(
        public string $name,
        public bool $multiple = false,
        public int $min = 0,
        public int $max = 0,
        public string $message = 'The value you selected is not a valid choice.',
        public string $multipleMessage = 'One or more of the given values is invalid.',
        public string $minMessage = 'You must select at least {{ limit }} choice.|You must select at least {{ limit }} choices.',
        public string $maxMessage = 'You must select at most {{ limit }} choice.|You must select at most {{ limit }} choices.',
        string|array $options = null,
        array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options, $groups, $payload);
    }
}
