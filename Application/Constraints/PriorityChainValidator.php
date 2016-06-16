<?php
/**
 * @package     Podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */

namespace Application\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Priority chain validation class
 */
class PriorityChainValidator extends ConstraintValidator {
	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed	$value The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		$group = $this->context->getGroup();
		$propertyPath = $this->context->getPropertyPath();

		$violationList = $this->context->getViolations();
		$violationCountPrevious = $violationList->count();

		/** @var \SplPriorityQueue $constraintsQueue */
		$constraintsQueue = $constraint->getConstraints();

		// change extraction mode to just data, we don't care about priority
		$constraintsQueue->setExtractFlags( \SplPriorityQueue::EXTR_DATA );

		foreach ( $constraintsQueue as $queuedConstraint ):
			$this->context->validateValue( $value, $queuedConstraint, $propertyPath, $group );

			if ( $constraint->stopOnError && ( count($violationList ) !== $violationCountPrevious ) ):
				return;
			endif;
		endforeach;
	}
}
