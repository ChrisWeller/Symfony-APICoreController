<?php

namespace PrimeSoftware\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

abstract class PageController extends BasePageController {

	#region Main index section - search / home page
	/**
	 * @Route("/", methods={"GET"})
	 * @param Request $request
	 * @return Response
	 */
	#[Route("/",methods:["GET"])]
	public function mainIndex( Request $request ) {
		return $this->allIndex( $request, false );
	}

	/**
	 * @Route("/subindex/", methods={"GET"})
	 * @param Request $request
	 * @return Response
	 */
	#[Route("/subindex/",methods:["GET"])]
	public function subIndex( Request $request ) {
		return $this->allIndex( $request, true );
	}
	#endregion

	#region Search - run search
	/**
	 * @Route("/search/", methods={"GET","POST"})
	 * @return Response
	 */
	#[Route("/search/",methods:["GET","POST"])]
	public function search( Request $request ) {
		return $this->_runSearch( $request );
	}
	#endregion

	#region Manage
	/**
	 * @Route("/{id<\d+>}", methods={"GET"})
	 * @return Response
	 */
	#[Route("/{id<\d+>}",methods:["GET"])]
	public function welcome( Request $request, int $id ) {
		return $this->allWelcome( $request, $id );
	}

	/**
	 * @Route("/subwelcome/{id<\d+>}", methods={"GET"})
	 * @return Response
	 */
	#[Route("/subwelcome/{id<\d+>}",methods:["GET"])]
	public function subWelcome( Request $request, $id ) {
		return $this->allWelcome( $request, $id, true );
	}
	#endregion
}
