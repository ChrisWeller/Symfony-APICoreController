<?php

namespace PrimeSoftware\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

abstract class SubPageController extends BasePageController {

	/**
	 * @var CLass for parent object
	 */
	protected $parent_class;

	#region Main index section - search / home page
	/**
	 * @Route("/", methods={"GET"})
	 * @param Request $request
	 * @return Response
	 */
	public function mainIndex( Request $request, int $parentId ) {
		return $this->allIndex( $request, false, $this->getParentObject( $parentId ) );
	}

	/**
	 * @Route("/subindex/", methods={"GET"})
	 * @param Request $request
	 * @return Response
	 */
	public function subIndex( Request $request, $parentId ) {
		return $this->allIndex( $request, true, $this->getParentObject( $parentId ) );
	}
	#endregion

	#region Search - run search
	/**
	 * @Route("/search/", methods={"GET","POST"})
	 * @return Response
	 */
	public function search( Request $request, $parentId ) {
		return $this->_runSearch( $request, $this->getParentObject( $parentId ) );
	}
	#endregion

	#region Manage
	/**
	 * @Route("/{id<\d+>}", methods={"GET"})
	 * @return Response
	 */
	public function welcome( Request $request, $id, $parentId ) {
		return $this->allWelcome( $request, $id, false, $this->getParentObject( $parentId ) );
	}

	/**
	 * @Route("/subwelcome/{id<\d+>}", methods={"GET"})
	 * @return Response
	 */
	public function subWelcome( Request $request, $id, $parentId ) {
		return $this->allWelcome( $request, $id, true, $this->getParentObject( $parentId ) );
	}
	#endregion

	#region Save
	/**
	 * @Route("/create", methods={"POST"})
	 * @return Response
	 */
	public function create( Request $request, $parent_id = null ) {
		$this->updateIsApi($request);

		return $this->_store( $request, null, $parent_id );
	}
	#endregion

	#region Support functions
	/**
	 * Get the individual item
	 * @param $id
	 * @return mixed
	 */
	protected function getParentObject( $id ) {
		return $this->em->find( $this->parent_class, $id );
	}
	#endregion
}
