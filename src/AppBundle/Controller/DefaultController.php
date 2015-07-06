<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    function rulesAction()
    {
    
    	$response = new Response();
    	$response->setPublic();
    	$response->setMaxAge($this->container->getParameter('cache_expiration'));
    
    	$page = $this->get('cards_data')->replaceSymbols($this->renderView('AppBundle:Default:rules.html.twig',
    			array("pagetitle" => "Rules", "pagedescription" => "Refer to the official rules of the game.")));
    	$response->setContent($page);
    	return $response;
    }
    
    function aboutAction()
    {
    
    	$response = new Response();
    	$response->setPublic();
    	$response->setMaxAge($this->container->getParameter('cache_expiration'));
    	
    	return $this->render('AppBundle:Default:about.html.twig', array(
    			"pagetitle" => "About",
    	), $response);
    }
    
    function apidocAction()
    {
    
    	$response = new Response();
    	$response->setPublic();
    	$response->setMaxAge($this->container->getParameter('cache_expiration'));
    	
    	return $this->render('AppBundle:Default:apidoc.html.twig', array(
    			"pagetitle" => "API documentation",
    	), $response);
    }
}
