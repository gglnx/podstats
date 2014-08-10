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
namespace Application\Controllers;

/**
 * The BaseController is base for all controllers this app
 */
class MasterController extends \Nautik\Controller {
	/**
	 *
	 */
	public $sentry;

	/**
	 *
	 */
	private $translator;

	/**
	 *
	 */
	private $formFactory;

	/**
	 *
	 */
	public function __construct( \Nautik\Nautik $application ) {
		parent::__construct( $application );

		// Initialize sentry
		$this->sentry = new \Cartalyst\Sentry\Sentry(
			new \Application\Repository\Users,
			new \Application\Repository\Groups
		);

		// Initialize translation
		$this->initializeTranslation( 'de' );

		// Initialize CSRF protection
		$csrfProvider = new \Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider(
			$this->getApplication()->session,
			getenv( 'CSRF_SECRET' )
		);

		// Add symfony/form to our Twig instance
		$this->getApplication()->templateRender->getLoader()->addPath(
			VENDOR . 'symfony/twig-bridge/Symfony/Bridge/Twig/Resources/views/Form'
		);

		$formEngine = new \Symfony\Bridge\Twig\Form\TwigRendererEngine( [ 'form.html.twig' ] );
		$formEngine->setEnvironment( $this->getApplication()->templateRender );

		$this->getApplication()->templateRender->addExtension(
			new \Symfony\Bridge\Twig\Extension\FormExtension(
				new \Symfony\Bridge\Twig\Form\TwigRenderer( $formEngine, $csrfProvider )
			)
		);

		// Initialize validation
		$validator = \Symfony\Component\Validator\Validation::createValidatorBuilder()
			->setTranslator( $this->getTranslator() )
			->setTranslationDomain( 'validators' )
			->getValidator();

		// Initialize form factory
		$this->formFactory = \Symfony\Component\Form\Forms::createFormFactoryBuilder()
			->addExtension( new \Symfony\Component\Form\Extension\Csrf\CsrfExtension( $csrfProvider ) )
			->addExtension( new \Symfony\Component\Form\Extension\Validator\ValidatorExtension( $validator ) )
			->getFormFactory();

		// Add braincrafted/bootstrap-bundle
		$this->getApplication()->templateRender->setExtensions( [
			new \Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapBadgeExtension(),
			new \Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension(),
			new \Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapIconExtension(),
			new \Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapFormExtension()
		] );
	}

	/**
	 * Translate timeframe URL parameter into DateTime and human
	 * readable format
	 */
	public function timeframe() {
		// Strings
		$strings = [
			'singluar' => ['h' => 'Eine Stunde', 'd' => 'Ein Tag', 'm' => 'Ein Monat', 'y' => 'Ein Jahr'],
			'plural' => ['h' => '%d Stunden', 'd' => '%d Tage', 'm' => '%d Monate', 'y' => '%d Jahre'],
			'today' => 'Heute',
			'yesterday' => 'Gestern',
			'month' => 'Aktueller Monat',
			'year' => 'Aktuelles Jahr'
		];

		// Get timeframe from URL
		$timeframe_attr = $this->attr("timeframe");

		// Timeframe
		$timeframe = (object) array("raw" => $timeframe_attr);

		// Current year
		if ( 'year' == $timeframe_attr ):
			// Query filter
			$timeframe->query = array(
				'$gte' => new \MongoDate((new \DateTime('first day of January'))->getTimestamp()),
				'$lt' => new \MongoDate((new \DateTime('first day of next year'))->modify('-1second')->getTimestamp())
			);

			// Human readable string
			$timeframe->human = $strings[$timeframe_attr];

			// Render labels as months
			$timeframe->label = 'MM.YYYY';

			// Add matched timeframe
			$timeframe->matched = ['year', 1, 'months', 'month'];

		// Current month
		elseif ( 'month' == $timeframe_attr ):
			// Query filter
			$timeframe->query = array(
				'$gte' => new \MongoDate((new \DateTime('first day of this month'))->getTimestamp()),
				'$lt' => new \MongoDate((new \DateTime('first day of next month'))->modify('-1second')->getTimestamp())
			);

			// Human readable string
			$timeframe->human = $strings[$timeframe_attr];

			// Render labels as months
			$timeframe->label = 'D.MM.';

			// Add matched timeframe
			$timeframe->matched = ['month', 31, 'days', 'day'];

		// Time since now filter
		elseif ( preg_match( '^(\d+)((hour|day|month|year)(s|))^i', $timeframe_attr, $matched ) ):
			// Query filter
			$timeframe->query = array(
				'$gt' => new \MongoDate((new \DateTime("-{$timeframe_attr}"))->getTimestamp())
			);

			// Human readable string
			$number = abs(intval($matched[1]));
			$mode = ( 1 == $number ) ? 'singluar' : 'plural';
			$timeframe->human = sprintf($strings[$mode][$matched[2]{0}], $number);

			// Render labels as hours, days or months
			if ( "hour" == $matched[3] || ( "day" == $matched[3] && 2 >= $matched[1] ) )
				$timeframe->label = 'HH:00';
			elseif ( "day" == $matched[3] )
				$timeframe->label = 'D.MM.';
			elseif ( "month" == $matched[3] && 1 == $matched[1] )
				$timeframe->label = 'D.MM.';
			else
				$timeframe->label = 'MM.YYYY';

			// Display one day as 24 hours, two days as 48 hours
			if ( "day" == $matched[3] && 2 >= $matched[1] )
				$matched = [( $matched[1] * 24 ) . 'hours', $matched[1] * 24, 'hours', 'hour'];
			// Display one month as 30 days
			elseif ( "month" == $matched[3] && 1 == $matched[1] )
				$matched = ['30days', 30, 'days', 'day'];

			// Add matched timeframe
			$timeframe->matched = $matched;
		// Yesterday / Today (default)
		else:
			// Start and end value
			$start = ( 'yesterday' == $timeframe_attr ) ? 'yesterday' : 'today';
			$end = ( 'yesterday' == $timeframe_attr ) ? 'today' : 'tomorrow';

			// Query filter
			$timeframe->query = array(
				'$gte' => new \MongoDate((new \DateTime($start))->getTimestamp()),
				'$lt' => new \MongoDate((new \DateTime($end))->getTimestamp())
			);

			// Human readable string
			$timeframe->human = $strings[$timeframe_attr];

			// Render labels as hours
			$timeframe->label = 'H:00';

			// Add matched timeframe
			$timeframe->matched = ['24hours', 24, 'hours', 'hour'];
		endif;

		return $timeframe;
	}

	/**
	 *
	 */
	public function getFormFactory() {
		return $this->formFactory;
	}

	/**
	 *
	 */
	public function getTranslator() {
		return $this->translator;
	}

	/**
	 *
	 */
	private function initializeTranslation( $language, $fallback = 'en' ) {
		// Initialize translation component
		$this->translator = new \Symfony\Component\Translation\Translator( $language );

		// Add XLF reader
		$this->translator->addLoader(
			'xlf',
			new \Symfony\Component\Translation\Loader\XliffFileLoader()
		);

		// Get translation files
		$translationFiles = \Symfony\Component\Yaml\Yaml::parse( APP . 'Config/translations.yml' );

		// Check if fallback lanugage exists
		if ( false == array_key_exists( $language, $translationFiles ) )
			throw new \RuntimeException( "No translation files for the fallback language [{$fallback}] exists." );

		// Use fallback language if language doesn't exists
		if ( false == array_key_exists( $language, $translationFiles ) )
			$language = $fallback;

		// Default options
		$defaultOptions = array(
			'type' => 'xlf',
			'domain' => null,
			'locationPrefix' => 'APP'
		);

		// Add translation files
		foreach ( $translationFiles[$language] as $key => $options ):
			// Merge options with default options
			$options = array_merge( $defaultOptions, $options );

			// Check if location exists
			if ( false == array_key_exists( 'location', $options ) )
				throw new \InvalidArgumentException( "Translation location is missing for {$key}." );

			// Add ressource
			$this->translator->addResource(
				$options['type'],
				constant( $options['locationPrefix'] ) . $options['location'],
				$language,
				$options['domain']
			);
		endforeach;

		// Add template extensions
		$this->getApplication()->templateRender->addExtension(
			new \Symfony\Bridge\Twig\Extension\TranslationExtension( $this->translator )
		);
	}
}
