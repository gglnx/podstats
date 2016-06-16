<?php
/**
 * @package     podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */

/**
 * Namespace
 */
namespace Application\Repository;

/**
 * Exceptions
 */
use \Cartalyst\Sentry\Groups\GroupNotFoundException;
use \Mongoium\NothingFoundException;

/**
 * Documents
 */
use \Application\Documents\Group;

/**
 * Groups
 *
 * @see         \Cartalyst\Sentry\Groups\Eloquent\Provider
 * @copyright   Copyright 2011 - 2013, Cartalyst LLC
 */
class Groups implements \Cartalyst\Sentry\Groups\ProviderInterface {
	/**
	 *
	 */
	public function findById( $id ) {
		try {
			return Group::query()->is( 'id', $id )->findOne();
		} catch ( NothingFoundException $e ) {
			throw new GroupNotFoundException( "No group [{$id}] found." );
		}
	}

	/**
	 *
	 */
	public function findByName( $name ) {
		try {
			return Group::query()->is( 'name', $name )->findOne();
		} catch ( NothingFoundException $e ) {
			throw new GroupNotFoundException( "No group [{$name}] found." );
		}
	}

	/**
	 *
	 */
	public function findAll() {
		return Group::query()->find();
	}

	/**
	 *
	 */
	public function create( array $attributes ) {
		$group = new Group( null, $attributes );
		$group->save();

		return $group;
	}
}
