<?php

namespace DesignComputing\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
	private $serializer;

	public function __construct( EntityManagerInterface $em, SerializerInterface $serializer ) {
		$this->em = $em;
		$this->serializer = $serializer;
	}

	/**
	 * @Route("/search/", methods={"GET"})
	 * @return Response
	 */
	public function search( Request $request ) {

		$objects = $this->_realSearch( $request );

		$response_data = $this->serializer->serialize( [ 'status' => 'OK', $this->plural_name => $objects ], 'json', ['groups' => $this->result_group ] );

		return new JsonResponse( $response_data, 200, [], true );
		//return new JsonResponse( [ $this->plural_name => $objects ] );
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	protected abstract function _realSearch( Request $request );

	/**
	 * @Route("/{id}", methods={"GET"}, requirements={"id"="\d+"})
	 * @return Response
	 */
	public function welcome( SerializerInterface $serializer, $id ) {

		// Strip anything that isn't a number
		$id = preg_replace( '/[^0-9]/', '', $id );

		// Get the object
		$object = $this->getObject( $id );

		// If there is no object
		if ( $object == null ) {
			return new JsonResponse( [ 'status' => 'Fail', 404 ] );
		}

		// Serialize the whole lot
		$response_data = $serializer->serialize( [ 'status' => 'OK', $this->singluar_name => $object ], 'json', ['groups' => 'admin' ] );

		return new JsonResponse( $response_data, 200, [], true );
		//return new JsonResponse( [ $this->singluar_name => $object ] );
	}

	/**
	 * @Route("/create", methods={"POST"})
	 * @return Response
	 */
	public function create( Request $request ) {
		return $this->_store( $request );
	}

	/**
	 * @Route("/save/{id}", methods={"POST"}, requirements={"id"="\d+"})
	 * @return Response
	 */
	public function save( Request $request, $id ) {
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

		$form->submit( $request->request->all(), false );

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
		$response_data = $this->serializer->serialize( [ 'status' => 'OK', $this->singluar_name => $object ], 'json', ['groups' => $this->result_group ] );

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
}
