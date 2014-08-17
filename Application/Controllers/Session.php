<?php
/**
 * @package     Podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */

namespace Application\Controllers;

use \Cartalyst\Sentry\Users;
use \Symfony\Component\Validator\Constraints as Assert;
use \Symfony\Component\Form\FormEvents;
use \Symfony\Component\Form\FormEvent;
use \Symfony\Component\Form\FormError;
use \Application\Constraints\PriorityChain;

/**
 *
 */
class Session extends MasterController {
	/**
	 *
	 */
	public function newAction() {
		// Create login form
		$form = $this->getFormFactory()->createBuilder( 'form', array() )
			// Set action
			->setAction( $this->path( 'login' ) )

			// Set method
			->setMethod( 'POST' )

			// Add username field
			->add( 'username', 'text', array(
				'label' => 'Benutzername',
				'constraints' => array( new PriorityChain( array(
					new Assert\NotBlank(),
					new Assert\Length( [ 'min' => 3, 'max' => 60 ] ),
					new Assert\Regex( [ 'pattern' => '/^[_a-zA-Za0-9]+$/' ] )
				) ) )
			) )

			// Add password field
			->add( 'password', 'password', array(
				'label' => 'Passwort',
				'constraints' => array( new PriorityChain( array(
					new Assert\NotBlank(),
					new Assert\Length( [ 'max' => 1024 ] )
				) ) )
			) )

			// Add reminder me field
			->add( 'remember', 'checkbox', array(
				'label' => 'Auf diesem Gerät eingeloggt bleiben?',
				'required' => false
			) )

			// Add submit button
			->add( 'submit', 'submit', array(
				'label' => 'Einloggen'
			) )

			// Origin
			->add( 'origin', 'hidden' )
			->addEventListener( FormEvents::POST_SET_DATA, function( FormEvent $event ) {
				$origin = $this->get( "origin", $event->getForm()->get( 'origin' )->getData() );
				if ( !preg_match( "/^[a-z0-9_.\/\-]+$/i", $origin ) )
					$origin = '/';
				$event->getForm()->get( 'origin' )->setData( $origin );
			} )

			// Get form
			->getForm();

		// Handle request
		$form->handleRequest();

		// Check if form valid
		if ( $form->isValid() ):
			// Get credentials from the form
			$data = $form->getData();

			// Try to login the user
			try {
				$user = $this->sentry->authenticate( array(
					'username' => $data['username'],
					'password' => $data['password']
				), $data['remember'] );

				return $this->redirect( $data['origin'] );

			// Wrong password
			} catch( Users\WrongPasswordException $e ) {
				$form->get( 'password' )->addError(
					new FormError( 'Das angebene Passwort ist falsch.' )
				);

			// User was not found
			} catch( Users\UserNotFoundException $e ) {
				$form->get( 'username' )->addError(
					new FormError( 'Dieser Benutzername exisiert nicht.' )
				);

			// User is not activated
			} catch( Users\UserNotActivatedException $e ) {
				$form->addError(
					new FormError( 'Dein Benutzerkonto ist noch nicht aktiviert. Bitte prüfe Dein Postfach und ggf. Deinen SPAM/Junk-Ordner nach der Bestätigungs-E-Mail.' )
				);
			}
		endif;

		return [ 'form' => $form->createView() ];
	}
}
