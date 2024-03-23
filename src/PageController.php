<?php

namespace PrimeSoftware\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;

abstract class PageController extends BasePageController {

	#region Main index section - search / home page
	/**
	 * @param Request $request
	 * @return Response
	 */
    #[Route('/', methods: ['GET'])]
	public function mainIndex( Request $request ) {
		return $this->allIndex( $request, false );
	}

	/**
	 * @param Request $request
	 * @return Response
	 */
    #[Route('/subindex', methods: ['GET'])]
	public function subIndex( Request $request ) {
		return $this->allIndex( $request, true );
	}
	#endregion

	#region Search - run search
	/**
	 * @return Response
	 */
    #[Route('/search/', methods: ['GET', 'POST'])]
	public function search( Request $request ) {
		return $this->_runSearch( $request );
	}
	#endregion

	#region Manage
	/**
	 * @return Response
	 */
    #[Route('/{id}', methods: ['GET'], requirements: ['id'=>'\d+'])]
	public function welcome( Request $request, int $id ) {
		return $this->allWelcome( $request, $id );
	}

	/**
	 * @return Response
	 */
    #[Route('/subwelcome/{id}', methods: ['GET'], requirements: ['id'=>'\d+'])]
	public function subWelcome( Request $request, $id ) {
		return $this->allWelcome( $request, $id, true );
	}
	#endregion
}
