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
use \Cartalyst\Sentry\Groups\NameRequiredException;
use \Cartalyst\Sentry\Groups\GroupExistsException;
use \Mongoium\NothingFoundException;

/**
 * Group
 *
 * @see         \Cartalyst\Sentry\Groups\Eloquent\Group
 * @copyright   Copyright 2011 - 2013, Cartalyst LLC
 */
class Group extends \Mongoium\Document implements \Cartalyst\Sentry\Groups\GroupInterface {
	/**
	 *
	 */
	public static $collectionName = 'groups';

	/**
	 *
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 *
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 *
	 */
	public function getPermissions() {
		return $this->premissions;
	}

	/**
	 *
	 */
	public function users() {
		$userRepository = new \Application\Repository\Users();

		return $userRepository->findAllInGroup( $this );
	}

	/**
	 *
	 */
	public function save() {
		// Validate document
		$this->validate();

		// Save document
		$this->save();
	}

	/**
	 *
	 */
	public function validate() {
		// Check if name field was passed
		if ( false == $this->name )
			throw new NameRequiredException( "A name is required for a group, none given." );

		// Check if group already exists
		try {
			$group = self::query()->is( 'name', $this->name )->findOne();

			if ( $group->getId() != $this->getId() ):
				throw new GroupExistsException( "A group already exists with name [{$this->name}], names must be unique for groups." );
			endif;
		} catch( NothingFoundException $e ) {
			return true;
		}

		return true;
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
	public function hasAccess( $permissions, $all = true ) {
		return $this->hasPermission( $permissions, $all );
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
	public function hasPermission( $permissions, $all = true ) {
		$groupPermissions = $this->getPermissions();

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

				foreach ( $groupPermissions as $groupPermission => $value ):
					// Strip the '*' off the end of the permission.
					$checkPermission = substr( $permission, 0, -1 );

					// We will make sure that the group permission does not
					// exactly match our permission, but starts with it.
					if ( $checkPermission != $groupPermission && strpos( $groupPermission, $checkPermission ) === 0 && $value == 1 ):
						$matched = true;
						break;
					endif;
				endforeach;

			elseif ( ( strlen( $permission ) > 1 ) and strpos( $permission, '*' ) === 0 ):
				$matched = false;

				foreach ( $groupPermissions as $groupPermission => $value ):
					// Strip the '*' off the beginning of the permission.
					$checkPermission = substr( $permission, 1 );

					// We will make sure that the group permission does not
					// exactly match our permission, but ends with it.
					if ( $checkPermission != $groupPermission && substr( $groupPermission, -strlen( $checkPermission ) ) === $checkPermission && $value == 1 ):
						$matched = true;
						break;
					endif;
				endforeach;

			else:
				$matched = false;

				foreach ( $groupPermissions as $groupPermission => $value ):
					// This time check if the groupPermission ends in wildcard "*" symbol.
					if ( ( strlen( $mergedPermission ) > 1) && substr( $mergedPermission, -strlen( '*' ) ) === '*' )
						$matched = false;

						// Strip the '*' off the end of the permission.
						$checkGroupPermission = substr( $groupPermission, 0, -1 );

						// We will make sure that the group permission does not
						// exactly match our permission, but starts with it.
						if ( $checkGroupPermission != $permission && strpos( $permission, $checkGroupPermission ) === 0 && $value == 1 )
							$matched = true;
							break;
						endif;

					// Otherwise, we'll fallback to standard permissions checking where
					// we match that permissions explicitly exist.
					elseif ( $permission == $groupPermission && $groupPermissions[$permission] == 1 ):
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
	public function delete() {
		// Delete group from users
		$users = $this->users();
		foreach ( $users as $user )
			$user->removeGroup( $this );

		// Delete document
		$this->delete();
	}
}
