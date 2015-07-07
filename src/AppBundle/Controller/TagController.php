<?php
namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Deck;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    
    public function addAction(Request $request)
    {
        $list_id = $request->get('ids');
        if(!is_array($list_id)) $list_id = explode(' ', $list_id);
        $list_tag = $request->get('tags');
        if(!is_array($list_tag)) $list_tag = explode(' ', $list_tag);
        
        $list_tag = array_map(function ($tag) { return preg_replace('/[^a-zA-Z0-9-]/', '', $tag); }, $list_tag);
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $response = array("success" => true);
        
        foreach($list_id as $id)
        {
            /* @var $deck Deck */
            $deck = $em->getRepository('AppBundle:Deck')->find($id);
            if(!$deck) continue;
            if ($this->getUser()->getId() != $deck->getUser()->getId()) continue;
            $tags = array_values(array_filter(array_unique(array_merge(explode(' ', $deck->getTags()), $list_tag)), function ($tag) { return $tag != ""; }));
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }
        $em->flush();
        
        return new Response(json_encode($response));
    }
    
    public function removeAction(Request $request)
    {
        $list_id = $request->get('ids');
        $list_tag = $request->get('tags');
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $response = array("success" => true);
        
        foreach($list_id as $id)
        {
            /* @var $deck Deck */
            $deck = $em->getRepository('AppBundle:Deck')->find($id);
            if(!$deck) continue;
            if ($this->getUser()->getId() != $deck->getUser()->getId()) continue;
            $tags = array_values(array_diff(explode(' ', $deck->getTags()), $list_tag));
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }
        $em->flush();
        
        return new Response(json_encode($response));
    }
	
	public function clearAction(Request $request)
	{
        $list_id = $request->get('ids');
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $response = array("success" => true);
        
        foreach($list_id as $id)
        {
            /* @var $deck Deck */
            $deck = $em->getRepository('AppBundle:Deck')->find($id);
            if(!$deck) continue;
            if ($this->getUser()->getId() != $deck->getUser()->getId()) continue;
            $response['tags'][$deck->getId()] = [];
            $deck->setTags('');
        }
        $em->flush();
        
        return new Response(json_encode($response));
    }
    
}