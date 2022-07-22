<?php

namespace App\Validator;

use App\Service\Choices;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class ChoiceValidator extends ConstraintValidator
{
    public function __construct(
        private Choices $choices,
    ) {}

    /**
     * @param mixed $value
     * @param Choice $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Choice) {
            throw new UnexpectedTypeException($constraint, Choice::class);
        }

        if (!$constraint->name || !$this->choices->support($constraint->name)) {
            throw new ConstraintDefinitionException('Choice "name" is required');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if ($constraint->multiple && !is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $choices = $this->choices->getChoices($constraint->name);

        if ($constraint->multiple) {
            foreach ($value as $_value) {
                if (!in_array($_value, $choices, true)) {
                    $this->context->buildViolation($constraint->multipleMessage)
                        ->setParameter('{{ value }}', $this->formatValue($_value))
                        ->setParameter('{{ choices }}', $this->formatValues($choices))
                        ->setInvalidValue($_value)
                        ->addViolation();

                    return;
                }
            }

            $count = count($value);

            if ($constraint->min > 0 && $count < $constraint->min) {
                $this->context->buildViolation($constraint->minMessage)
                    ->setParameter('{{ limit }}', $constraint->min)
                    ->setPlural((int) $constraint->min)
                    ->addViolation();

                return;
            }

            if ($constraint->max > 0 && $count > $constraint->max) {
                $this->context->buildViolation($constraint->maxMessage)
                    ->setParameter('{{ limit }}', $constraint->max)
                    ->setPlural((int) $constraint->max)
                    ->addViolation();

                return;
            }
        } elseif (!in_array($value, $choices, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ choices }}', $this->formatValues($choices))
                ->addViolation();
        }
    }
}
