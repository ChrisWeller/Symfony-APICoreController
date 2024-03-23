<?php

namespace PrimeSoftware\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;

abstract class CoreController extends AbstractController {

	/**
	 * @var string Singular name for return value
	 */
	protected $singluar_name;

	/**
	 * @var string Plural name for return value
	 */
	protected $plural_name;

	/**
	 * @var string Class name of the form task
	 */
	protected $form_class;

	/**
	 * @var string Class name of the object
	 */
	protected $object_class;

	/**
	 * @var string Group used to choose the fields to return
	 */
	protected $result_group = null;

	/**
	 * @var EntityManagerInterface
	 */
	protected $em;

	/**
	 * @var SerializerInterface
	 */
	protected $serializer;

	/**
	 * Whether this is being access via the api or 'old fashioned' html forms
	 * @var bool
	 */
	protected $is_api = false;

	public function __construct( EntityManagerInterface $em, SerializerInterface $serializer ) {
		$this->em = $em;
		$this->serializer = $serializer;
	}

	/**
	 * @return Response
	 */
    #[Route('/search/', methods: ['GET'])]
	public function search( Request $request ) {
		$this->is_api = !empty( $request->headers->get( 'X-AUTH') );

		$objects = $this->_realSearch( $request );

		if ( $this->is_api ) {
			$response_data = $this->serializer->serialize( [ 'status' => 'OK', $this->plural_name => $objects ], 'json', [ 'groups' => $this->result_group ] );

			return new JsonResponse( $response_data, 200, [], true );
		}
		else {
			$this->twigData[ 'results' ] = $objects;
			$this->postSearchData( $objects );

			return $this->render( $this->template_path . '/search.html.twig', $this->twigData );
		}
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	protected abstract function _realSearch( Request $request );

	/**
	 * @return Response
	 */
    #[Route('/{id}/{subpage}', methods: ['GET'], requirements: ['id'=>'\d+', 'subpage'=>'\d+'])]
	public function welcome( Request $request, SerializerInterface $serializer, int $id, $subpage = 0) {
		$this->is_api = !empty( $request->headers->get( 'X-AUTH') );

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
			$response_data = $serializer->serialize( [ 'status' => 'OK', $this->singluar_name => $object ], 'json', [ 'groups' => $this->result_group ] );

			return new JsonResponse( $response_data, 200, [], true );
		}
		else {
			if ( $object === null ) {
				$object = new $this->object_class();
			}
			$form = $this->createForm( $this->manageFormClass, $object );
			$this->twigData[ 'page' ][ 'form' ] = $form->createView();
			$this->twigData[ 'page' ][ 'subpage' ] = $subpage == 1;
			$this->twigData[ 'object' ] = $object;

			return $this->render( $this->template_path . '/manage.html.twig', $this->twigData );
		}
	}

	/**
	 * @return Response
	 */
    #[Route('/create', methods: ['POST'])]
	public function create( Request $request ) {
		$this->is_api = !empty( $request->headers->get( 'X-AUTH') );

		return $this->_store( $request );
	}

	/**
	 * @return Response
	 */
    #[Route('/save/{id}', methods: ['POST'], requirements: ['id'=>'\d+'])]
	public function save( Request $request, $id ) {
		$this->is_api = !empty( $request->headers->get( 'X-AUTH') );

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

		$form = $this->createForm( $this->form_class, $object );
		$content = $request->getContent();

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
		if ( $form->isValid() ) {
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
	 * Get the individual item
	 * @param $id
	 * @return mixed
	 */
	protected function getObject( $id ) {
		return $this->em->find( $this->object_class, $id );
	}

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
	 * @param Request $request
	 */
    #[Route('/index', methods: ['GET'])]
	public function mainIndex( Request $request ) {
		$this->twigData[ 'page' ][ 'subpage' ] = false;
		return $this->allIndex( $request );
	}

	/**
	 * @param Request $request
	 */
    #[Route('/subindex', methods: ['GET'])]
	public function subIndex( Request $request ) {
		$this->twigData[ 'page' ][ 'subpage' ] = true;
		return $this->allIndex( $request );
	}

	/**
	 * Actually renders the twig template (with additional data if required)
	 * @param Request $request
	 * @return Response
	 */
	private function allIndex( Request $request ) {
		// Note if the page is a manage page
		$this->twigData[ 'page' ][ 'manage' ] = false;
		$this->twigData[ 'page' ][ 'subpage' ] = 0;

		// Get the search data added to the array
		$this->retrieveSearchData();

		$form = $this->createForm( $this->searchFormClass );
		$this->twigData[ 'page' ][ 'form' ] = $form->createView();

		// Render the template
		return $this->render( $this->template_path . '/index.html.twig', $this->twigData );
	}

	/**
	 * Adds additional data to the twig data array for the search screen
	 */
	protected function retrieveSearchData() {

	}

	/**
	 * Add additional data to the twid data array for the post search
	 * @param $objects
	 */
	protected function postSearchData( $objects ) {

	}
}
