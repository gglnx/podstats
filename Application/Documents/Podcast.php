<?php
/**
 * @package     Podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */

/**
 * Namespace
 */
namespace Application\Documents;

/**
 * Dependencies
 */
use \Hateoas\Configuration\Annotation as Hateoas;

/**
 * Podcast
 *
 * @Hateoas\Relation(
 *     name = "self",
 *     href = "expr('/api/users/' ~ object.getId())"
 * )
 */
class Podcast extends \Mongoium\Document {
	/**
	 * @var string $collectionName Name of the MongoDB collection
	 */
	public static $collectionName = 'podcasts';
}
