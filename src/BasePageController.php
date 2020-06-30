<?php

namespace PrimeSoftware\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

abstract class BasePageController extends AbstractController {


	/**
	 * @var string Singular name for return value
	 */
	protected $singluar_name;

	/**
	 * @var string Plural name for return value
	 */
	protected $plural_name;

	/**
	 * @var EntityManagerInterface
	 */
	protected $em;

	/**
	 * @var SerializerInterface
	 */
	protected $serializer;

	/**
	 * Path to templates
	 * @var string
	 */
	protected $template_path;

	/**
	 * Data to pass to twig
	 * @var array
	 */
	protected $twigData = [];

	/**
	 * Holds the search form
	 * @var string
	 */
	protected $searchFormClass = '';

	/**
	 * Holds the management form
	 * @var string
	 */
	protected $manageFormClass = '';

	/**
	 * PageController constructor.
	 * @param EntityManagerInterface $em
	 * @param SerializerInterface $serializer
	 */
	public function __construct( EntityManagerInterface $em, SerializerInterface $serializer ) {
		$this->em = $em;
		$this->serializer = $serializer;
	}

	#region Main index section - search / home page
	/**
	 * Actually renders the twig template (with additional data if required)
	 * @param Request $request
	 * @param bool $subpage
	 * @return Response
	 */
	protected function allIndex( Request $request, $subpage = false, $parentEntity = null ) {
		$this->updateIsApi($request);

		// Note if the page is a manage page
		$this->twigData[ 'page' ][ 'manage' ] = false;
		$this->twigData[ 'page' ][ 'subpage' ] = $subpage;
		$this->twigData[ 'parent' ] = $parentEntity;
		$form = $this->createForm( $this->searchFormClass );

		// Get the search data added to the array
		$this->retrieveSearchData( $form, $parentEntity );

		// Create the view
		$this->twigData[ 'page' ][ 'form' ] = $form->createView();

		// Render the template
		return $this->render( $this->template_path . '/index.html.twig', $this->twigData );
	}

	/**
	 * Adds additional data to the twig data array for the search screen
	 */
	protected function retrieveSearchData( Form $form, $parentEntity = null ) {

	}
	#endregion

	#region Search - run search
	protected function _runSearch( Request $request, $parentEntity = null ) {
		$this->updateIsApi($request);

		// Build the search form
		$form = $this->createForm( $this->searchFormClass );

		// If this is the api
		if ( $this->is_api ) {
			// Decode the content
			$data = json_decode(
				$request->getContent(),
				true
			);

			// Submit the data to the form
			$form->submit( $data, false );
		}
		else {
			// Handle the request
			$form->handleRequest( $request );
		}

		// Actually run the search
		$objects = $this->_realSearch( $request, $form, $parentEntity );

		if ( $this->is_api ) {
			$response_data = $this->serializer->serialize( [ 'status' => 'OK', $this->plural_name => $objects ], 'json', [ 'groups' => $this->result_group ] );

			return new JsonResponse( $response_data, 200, [], true );
		}
		else {
			$this->twigData[ 'results' ] = $objects;
			$this->postSearchData( $objects, $parentEntity );

			return $this->render( $this->template_path . '/search.html.twig', $this->twigData );
		}
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	protected abstract function _realSearch( Request $request, Form $form, $parentEntity = null );

	/**
	 * Add additional data to the twig data array for the post search
	 * @param $objects
	 */
	protected function postSearchData( $objects, $parentEntity = null ) {

	}
	#endregion

	#region Manage
	protected function allWelcome( Request $request, $id, $subpage = false, $parentEntity = null ) {
		$this->updateIsApi($request);

		// Strip anything that isn't a number
		$id = preg_replace( '/[^0-9]/', '', $id );

		// Get the object
		$object = $this->getObject( $id );

		if ( $this->is_api ) {
			// If there is no object
			if ( $object == null ) {
				return new JsonResponse( [ 'status' => 'Fail', 404 ] );
			}

			// Serialize the whole lot
			$response_data = $this->serializer->serialize( [ 'status' => 'OK', $this->singluar_name => $object ], 'json', [ 'groups' => $this->result_group ] );

			return new JsonResponse( $response_data, 200, [], true );
		}
		else {
			if ( $object === null ) {
				$object = new $this->object_class();
				$this->_postNewObject( $object );
			}
			$this->_preCreateForm( $object, $parentEntity );
			$form = $this->createForm( $this->manageFormClass, $object );
			$this->twigData[ 'page' ][ 'form' ] = $form->createView();
			$this->twigData[ 'page' ][ 'subpage' ] = $subpage == 1;
			$this->twigData[ 'parent' ] = $parentEntity;
			$this->twigData[ 'object' ] = $object;

			$this->_getAdditionalManageData( $object );

			return $this->render( $this->template_path . '/manage.html.twig', $this->twigData );
		}
	}

	protected function _getAdditionalManageData( $object ) {

	}

	protected function _preCreateForm( $object ) {

	}

	protected function _postNewObject( $object ) {

	}
	#endregion

	#region Save
	/**
	 * @Route("/create", methods={"POST"})
	 * @return Response
	 */
	public function create( Request $request ) {
		$this->updateIsApi($request);

		return $this->_store( $request );
	}

	/**
	 * @Route("/{id}", methods={"POST"}, requirements={"id"="\d+"})
	 * @return Response
	 */
	public function save( Request $request, $id ) {
		$this->updateIsApi($request);

		return $this->_store( $request, $id );
	}

	/**
	 * Store the object
	 * @param Request $request
	 * @param null $id
	 * @return JsonResponse
	 */
	private function _store( Request $request, $id = null ) {

		if ( $id ) {
			$object = $this->getObject( $id );
		}
		else {
			$object = new $this->object_class();
		}

		$form = $this->createForm( $this->manageFormClass, $object );

		if ( $this->is_api ) {
			// Data received via json - so decode content
			$data = json_decode(
				$request->getContent(),
				true
			);

			$form->submit( $data, false );
		}
		else {
			// Must be posted via standard form
			$form->handleRequest( $request );
		}

		// If the form is valid
		if ( $form->isSubmitted() && $form->isValid() ) {

			// Call the post save function
			$this->_postSaveObject( $object, ( $this->is_api ? $data : $request ) );

			return $this->_saveObject( $object );
		}
		else {
			$errors = $form->getErrors( true );
			$error_notes = [];
			foreach( $errors as $error ) {
				$error_notes[] = sprintf( "Field '%s' - %s", $error->getOrigin()->getName(), $error->getMessage() );
			}

			// Warn the user
			return new JsonResponse( [ "status" => "Fail", "notes" => "Please complete all required fields", "errors" => $error_notes ] );
		}
	}

	/**
	 * Actually save the object
	 * @param $object
	 * @return JsonResponse
	 */
	protected function _saveObject( $object ) {
		// Persist the data
		$this->em->persist( $object );

		// Flush the data to the database
		$this->em->flush();

		// Serialize the whole lot
		$response_data = $this->serializer->serialize( [ 'status' => 'OK', 'id' => $object->getId(), $this->singluar_name => $object ], 'json', ['groups' => $this->result_group ] );

		// Return success :-)
		return new JsonResponse( $response_data, 200, [], true );
	}

	/**
	 * @param $object Entity
	 * @param $data Request
	 */
	protected function _postSaveObject( $object, $data ) {

	}
	#endregion

	#region Support Functions
	private function updateIsApi( Request $request ) {
		$this->is_api = !empty( $request->headers->get( 'X-AUTH-TOKEN') );
	}

	/**
	 * Get the individual item
	 * @param $id
	 * @return mixed
	 */
	protected function getObject( $id ) {
		return $this->em->find( $this->object_class, $id );
	}
	#endregion
}
