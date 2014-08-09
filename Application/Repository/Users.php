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
use \Cartalyst\Sentry\Users\UserNotActivatedException;
use \Cartalyst\Sentry\Users\UserNotFoundException;
use \Cartalyst\Sentry\Users\WrongPasswordException;
use \Mongoium\NothingFoundException;

/**
 * Documents
 */
use \Application\Documents\User;

/**
 * Users
 *
 * @see         \Cartalyst\Sentry\Users\Eloquent\Provider
 * @copyright   Copyright 2011 - 2013, Cartalyst LLC
 */
class Users implements \Cartalyst\Sentry\Users\ProviderInterface {
	/**
	 *
	 */
	public function findById( $id ) {
		try {
			return User::query()->is( 'id', $id )->findOne();
		} catch ( NothingFoundException $e ) {
			throw new UserNotFoundException( "No user [{$id}] found." );
		}
	}

	/**
	 *
	 */
	public function findByLogin( $login ) {
		try {
			return User::query()->is( 'username', $login )->findOne();
		} catch ( NothingFoundException $e ) {
			throw new UserNotFoundException( "No user [{$login}] found." );
		}
	}

	/**
	 *
	 */
	public function findByCredentials( array $credentials ) {
		// Check if login was provided
		if ( !array_key_exists( 'username', $credentials ) )
			throw new \InvalidArgumentException( "Login attribute [username] was not provided." );

		// Check if password was provided
		if ( !array_key_exists( 'password', $credentials ) )
			throw new \InvalidArgumentException( "Password attribute [password] was not provided." );

		// Create query
		$query = User::query();

		// Add all provided informations, except password, to the query
		foreach ( $credentials as $credential => $value ):
			if ( 'password' != $credential ):
				$query = $query->is( $credential, $value );
			endif;
		endforeach;

		// Try to find the user
		try {
			$user = $query->findOne();

			// Check if the provided password is correct
			if ( false == $user->checkPassword( $credentials['password'] ) )
				throw new WrongPasswordException( "A wrong password was provided." );

			return $user;

		} catch( NothingFoundException $e ) {
			throw new UserNotFoundException( "A user was not found with the given credentials." );
		}
	}

	/**
	 *
	 */
	public function findByActivationCode( $code ) {
		// Try to find user
		try {
			$query = User::query()->is( 'activationCode', $code );

			if ( 1 < ( $count = $query->countAll() ) )
				throw new \RuntimeException( "Found [{$count}] users with the same activation code." );

			return $query->findOne();

		} catch( NothingFoundException $e ) {
			throw new UserNotFoundException( "A user was not found with the given activation code." );
		}
	}

	/**
	 *
	 */
	public function findByResetPasswordCode( $code ) {
		// Try to find user
		try {
			$query = User::query()->is( 'resetPasswordCode', $code );

			if ( 1 < ( $count = $query->countAll() ) )
				throw new \RuntimeException( "Found [{$count}] users with the same reset password code." );

			return $query->findOne();

		} catch( NothingFoundException $e ) {
			throw new UserNotFoundException( "A user was not found with the given reset password code." );
		}
	}

	/**
	 *
	 */
	public function findAll() {
		return User::query()->find();
	}

	/**
	 *
	 */
	public function findAllInGroup( \Cartalyst\Sentry\Groups\GroupInterface $group ) {
		return User::query()->is( 'groups', $group->__toDBRef() )->find();
	}

	/**
	 *
	 */
	public function findAllWithAccess( $permissions ) {
		return array_filter( $this->findAll(), function( $user ) use ( $permissions ) {
			return $user->hasAccess( $permissions );
		} );
	}

	/**
	 *
	 */
	public function findAllWithAnyAccess( array $permissions ) {
		return array_filter( $this->findAll(), function( $user ) use ( $permissions ) {
			return $user->hasAnyAccess( $permissions );
		} );
	}

	/**
	 *
	 */
	public function create( array $credentials ) {
		$user = new User( null, $credentials );
		$user->save();

		return $user;
	}

	/**
	 *
	 */
	public function getEmptyUser() {
		return new User();
	}
}
