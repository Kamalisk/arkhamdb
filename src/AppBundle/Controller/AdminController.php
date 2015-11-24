<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
	public function indexAction()
	{
		return $this->render('AppBundle:Admin:index.html.twig');
	}
}
