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
namespace Application\Documents;

/**
 * Exceptions
 */
use \Cartalyst\Sentry\Users\LoginRequiredException;
use \Cartalyst\Sentry\Users\PasswordRequiredException;
use \Cartalyst\Sentry\Users\UserExistsException;
use \Cartalyst\Sentry\Users\UserAlreadyActivatedException;
use \Cartalyst\Sentry\Users\UserNotFoundException;
use \Cartalyst\Sentry\Users\WrongPasswordException;
use \Cartalyst\Sentry\Groups\GroupInterface;
use \Mongoium\NothingFoundException;

/**
 * User
 *
 * @see         \Cartalyst\Sentry\Users\Eloquent\User
 * @copyright   Copyright 2011 - 2013, Cartalyst LLC
 */
class User extends \Mongoium\Document implements \Cartalyst\Sentry\Users\UserInterface {
	/**
	 *
	 */
	public static $collectionName = 'users';

	/**
	 *
	 */
	private $__mergedPermissions = array();

	/**
	 *
	 */
	public function __set( $parameter, $value ) {
		if ( $parameter == $this->getPasswordName() )
			$value = password_hash( $value, PASSWORD_DEFAULT );

		parent::__set( $parameter, $value );
	}

	/**
	 *
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 *
	 */
	public function getLoginName() {
		return 'username';
	}

	/**
	 *
	 */
	public function getLogin() {
		return $this->{$this->getLoginName()};
	}

	/**
	 *
	 */
	public function getPasswordName() {
		return 'password';
	}

	/**
	 *
	 */
	public function getPassword() {
		return $this->{$this->getPasswordName()};
	}

	/**
	 *
	 */
	public function isActivated() {
		return (bool) $this->activated;
	}

	/**
	 *
	 */
	public function isSuperUser() {
		return $this->hasPermission( 'superuser' );
	}

	/**
	 *
	 */
	public function validate() {
		// Check if login was added
		if ( false == ( $login = $this->getLogin() ) )
			throw new LoginRequiredException( "A login is required for a user, none given." );

		// Check if password was added
		if ( false == $this->getPassword() )
			throw new PasswordRequiredException( "A password is required for user [{$login}], none given." );

		// Check if the user already exists
		try {
			$user = self::query()->is( $this->getLoginName(), $login )->findOne();

			if ( $user->getId() != $this->getId() ):
				throw new UserExistsException( "A user already exists with login [{$login}], logins must be unique for users." );
			endif;
		} catch( NothingFoundException $e ) {
			return true;
		}

		return true;
	}

	/**
	 *
	 */
	public function save() {
		// Validate document
		$this->validate();

		// Save document
		parent::save();
	}

	/**
	 *
	 */
	public function delete() {
		$this->delete();
	}

	/**
	 *
	 */
	public function getPersistCode() {
		// Prepare generator
		$factory = new \RandomLib\Factory;
		$generator = $factory->getMediumStrengthGenerator();

		// Generate a random string as a persist code
		$this->persistCode = $generator->generateString( 128 );

		// Save user
		$this->save();

		return $this->persistCode;
	}

	/**
	 *
	 */
	public function checkPersistCode( $persistCode ) {
		if ( false == $persistCode )
			return false;

		return $persistCode == $this->persistCode;
	}

	/**
	 *
	 */
	public function getActivationCode() {
		// Prepare generator
		$factory = new \RandomLib\Factory;
		$generator = $factory->getMediumStrengthGenerator();

		// Generate a random string as activation code
		$this->activationCode = $generator->generateString( 32, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );

		// Save user
		$this->save();

		return $this->activationCode;
	}

	/**
	 *
	 */
	public function attemptActivation( $activationCode ) {
		// Check is user is already activated
		if ( $this->isActivated() )
			throw new UserAlreadyActivatedException( 'Cannot attempt activation on an already activated user.' );

		// Activate user if code is correct
		if ( $activationCode == $this->activationCode ):
			$this->activationCode = null;
			$this->activated = true;
			$this->activatedAt = new \DateTime();

			return $this->save();
		endif;

		return false;
	}

	/**
	 *
	 */
	public function checkPassword( $password ) {
		// Check password
		if ( password_verify( $password, $this->getPassword() ) ):
			// Check if password needs rehash
			if ( password_needs_rehash( $this->getPassword(), PASSWORD_DEFAULT ) ):
				$this->{$this->getPasswordName()} = password_hash( $password, PASSWORD_DEFAULT );
				$this->save();
			endif;

			return true;
		endif;

		return false;
	}

	/**
	 *
	 */
	public function getResetPasswordCode() {
		// Prepare generator
		$factory = new \RandomLib\Factory;
		$generator = $factory->getMediumStrengthGenerator();

		// Generate a random string as reset password code
		$this->resetPasswordCode = $generator->generateString( 32, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );

		// Save user
		$this->save();

		return $this->resetPasswordCode;
	}

	/**
	 *
	 */
	public function checkResetPasswordCode( $resetCode ) {
		return $resetPasswordCode == $this->resetPasswordCode;
	}

	/**
	 *
	 */
	public function attemptResetPassword( $resetCode, $newPassword ) {
		if ( $this->checkResetPasswordCode( $resetCode ) ):
			$this->resetPasswordCode = null;
			$this->{$this->getPasswordName()} = password_hash( $password, PASSWORD_DEFAULT );

			return $this->save();
		endif;

		return false;
	}

	/**
	 *
	 */
	public function clearResetPassword() {
		if ( $this->resetPasswordCode ):
			$this->resetPasswordCode = null;
			$this->save();
		endif;
	}

	/**
	 *
	 */
	public function getGroups() {
		if ( false == is_array( $this->groups ) )
			return array();

		// Get group ids
		$groupIds = array_map( $this->groups, function( $group ) {
			return $group['$id'];
		} );

		return Groups::query()->in( 'id', $groupIds )->find();
	}

	/**
	 *
	 */
	public function addGroup( \Cartalyst\Sentry\Groups\GroupInterface $group ) {
		if ( false == is_array( $this->groups ) )
			$this->groups = array();

		// Add user to group
		if ( $this->inGroup( $group ) )
			$this->groups[] = $group->__toDBRef();

		// Save user
		$this->save();

		return true;
	}

	/**
	 *
	 */
	public function removeGroup( GroupInterface $group ) {
		if ( false == is_array( $this->groups ) )
			$this->groups = array();

		// Add user to group
		if ( $this->inGroup( $group ) )
			if ( false !== ( $key = array_search( $group->__toDBRef(), $this->groups ) ) )
				unset( $this->groups[$key] );

		// Save user
		$this->save();

		return true;
	}

	/**
	 *
	 */
	public function inGroup( GroupInterface $group ) {
		if ( false == is_array( $this->groups ) )
			$this->groups = array();

		// Get database reference of group
		$group = $group->__toDBRef();

		// Check if user is part of group
		return in_array( $group, $this->groups );
	}

	/**
	 *
	 */
	public function getPermissions() {
		return (array) $this->permissions;
	}

	/**
	 *
	 */
	public function setPermissions( array $permissions ) {
		// Merge permissions
		$permissions = array_merge( $this->getPermissions(), $permissions );

		// Loop through and adjust permissions as needed
		foreach ( $permissions as $permission => &$value ):
			// Lets make sure there is a valid permission value
			if ( !in_array( $value = (int) $value, [ -1, 0, 1 ] ) ):
				throw new \InvalidArgumentException( "Invalid value [{$value}] for permission [{$permission}] given." );
			endif;

			// If the value is 0, delete it
			if ( 0 === $value ):
				unset( $permissions[$permission] );
			endif;
		endforeach;

		// Set permissions and save
		$this->permissions = $permissions;

		return $this->save();
    }

	/**
	 *
	 */
	public function getMergedPermissions() {
		if ( false == $this->__mergedPermissions ):
			$permissions = array();

			foreach ( $this->getGroups() as $group )
				$permissions = array_merge( $permissions, $group->getPermissions() );

			$this->__mergedPermissions = array_merge( $permissions, $this->getPermissions() );
		endif;

		return $this->__mergedPermissions;
	}

	/**
	 *
	 */
	public function hasAccess( $permissions, $all = true ) {
		if ( $this->isSuperUser() )
			return true;

		return $this->hasPermission( $permissions, $all );
	}

	/**
	 *
	 */
	public function hasPermission( $permissions, $all = true ) {
		$mergedPermissions = $this->getMergedPermissions();

		if ( !is_array( $permissions ) )
			$permissions = (array) $permissions;

		foreach ( $permissions as $permission ):
			// We will set a flag now for whether this permission was matched at all.
			$matched = true;

			// Now, let's check if the permission ends in a wildcard "*" symbol.
			// If it does, we'll check through all the merged permissions to see
			// if a permission exists which matches the wildcard.
			if ( ( strlen( $permission ) > 1 ) && substr( $permission, -strlen( '*' ) ) === '*' ):
				$matched = false;

				foreach ( $mergedPermissions as $mergedPermission => $value ):
					// Strip the '*' off the end of the permission.
					$checkPermission = substr( $permission, 0, -1 );

					// We will make sure that the merged permission does not
					// exactly match our permission, but starts with it.
					if ( $checkPermission != $mergedPermission && strpos( $mergedPermission, $checkPermission ) === 0 && $value == 1 ):
						$matched = true;
						break;
					endif;
				endforeach;

			elseif ( ( strlen( $permission ) > 1 ) and strpos( $permission, '*' ) === 0 ):
				$matched = false;

				foreach ( $mergedPermissions as $mergedPermission => $value ):
					// Strip the '*' off the beginning of the permission.
					$checkPermission = substr( $permission, 1 );

					// We will make sure that the merged permission does not
					// exactly match our permission, but ends with it.
					if ( $checkPermission != $mergedPermission && substr( $mergedPermission, -strlen( $checkPermission ) ) === $checkPermission && $value == 1 ):
						$matched = true;
						break;
					endif;
				endforeach;

			else:
				$matched = false;

				foreach ( $mergedPermissions as $mergedPermission => $value ):
					// This time check if the mergedPermission ends in wildcard "*" symbol.
					if ( ( strlen( $mergedPermission ) > 1) && substr( $mergedPermission, -strlen( '*' ) ) === '*' ):
						$matched = false;

						// Strip the '*' off the end of the permission.
						$checkMergedPermission = substr($mergedPermission, 0, -1);

						// We will make sure that the merged permission does not
						// exactly match our permission, but starts with it.
						if ( $checkMergedPermission != $permission && strpos( $permission, $checkMergedPermission ) === 0 && $value == 1 ):
							$matched = true;
							break;
						endif;

					// Otherwise, we'll fallback to standard permissions checking where
					// we match that permissions explicitly exist.
					elseif ( $permission == $mergedPermission && $mergedPermissions[$permission] == 1 ):
						$matched = true;
						break;
					endif;
				endforeach;
			endif;

			// Now, we will check if we have to match all
			// permissions or any permission and return
			// accordingly.
			if ( $all === true && $matched === false ):
				return false;
			elseif ( $all === false && $matched === true ):
				return true;
			endif;
		endforeach;

		if ( $all === false )
			return false;

		return true;
	}

	/**
	 *
	 */
	public function hasAnyAccess( array $permissions ) {
		return $this->hasAccess( $permissions, false );
	}

	/**
	 *
	 */
	public function recordLogin() {
		$this->lastLogin = new \DateTime();
		$this->save();
	}
}
