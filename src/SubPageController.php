<?php

namespace PrimeSoftware\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

abstract class SubPageController extends BasePageController {

	/**
	 * @var CLass for parent object
	 */
	protected $parent_class;

	#region Main index section - search / home page
	/**
	 * @param Request $request
	 * @return Response
	 */
    #[Route('/', methods: ['GET'])]
	public function mainIndex( Request $request, int $parentId ) {
		return $this->allIndex( $request, false, $this->getParentObject( $parentId ) );
	}

	/**
	 * @param Request $request
	 * @return Response
	 */
    #[Route('/subindex/', methods: ['GET'])]
	public function subIndex( Request $request, $parentId ) {
		return $this->allIndex( $request, true, $this->getParentObject( $parentId ) );
	}
	#endregion

	#region Search - run search
	/**
	 * @return Response
	 */
    #[Route('/search/', methods: ['GET', 'POST'])]
	public function search( Request $request, $parentId ) {
		return $this->_runSearch( $request, $this->getParentObject( $parentId ) );
	}
	#endregion

	#region Manage
	/**
	 * @return Response
	 */
    #[Route('/{id}', methods: ['GET'], requirements: ['id'=>'\d+'])]
	public function welcome( Request $request, $id, $parentId ) {
		return $this->allWelcome( $request, $id, false, $this->getParentObject( $parentId ) );
	}

	/**
	 * @return Response
	 */
    #[Route('/subwelcome/{id}', methods: ['GET'], requirements: ['id'=>'\d+'])]
	public function subWelcome( Request $request, $id, $parentId ) {
		return $this->allWelcome( $request, $id, true, $this->getParentObject( $parentId ) );
	}
	#endregion

	#region Save
	/**
	 * @return Response
	 */
    #[Route('/create', methods: ['POST'])]
	public function create( Request $request, $parentId = null ) : JsonResponse {
		$this->updateIsApi($request);

		return $this->_store( $request, null, $parentId );
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
