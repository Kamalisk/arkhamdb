<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Card;

class ExcelController extends Controller
{
	public function downloadFormAction()
	{
		$em = $this->getDoctrine()->getManager();
		$packs = $em->getRepository('AppBundle:Pack')->findBy([], ['dateRelease' => 'ASC', 'name' => 'ASC']);
		return $this->render('AppBundle:Excel:download_form.html.twig', [
				'packs' => $packs
		]);
	}
	
	public function downloadProcessAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$pack_id = $request->request->get('pack');
		$pack = $em->getRepository('AppBundle:Pack')->find($pack_id);
		$cards = $em->getRepository('AppBundle:Card')->findBy(['pack' => $pack], ['code' => 'ASC']);
		
		$fieldNames = $em->getClassMetadata('AppBundle:Card')->getFieldNames();
		
		$associationMappings = $em->getClassMetadata('AppBundle:Card')->getAssociationMappings();
		
		/* @var $card \AppBundle\Entity\Card */
		foreach($cards as $card) {
			if(empty($lastModified) || $lastModified < $card->getDateUpdate()) $lastModified = $card->getDateUpdate();
		}
		
		$phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
		$phpExcelObject->getProperties()
			->setCreator("Alsciende")
			->setLastModifiedBy($lastModified->format('Y-m-d'))
			->setTitle($pack->getName())
		;
		$phpActiveSheet = $phpExcelObject->setActiveSheetIndex(0);
		$phpActiveSheet->setTitle($pack->getName());
		
		$col_index = 0;
		foreach($associationMappings as $fieldName => $associationMapping)
		{
			if($associationMapping['isOwningSide']) {
				$phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, 1);
				$phpCell->setValue($fieldName);
			}
		}
		foreach($fieldNames as $fieldName)
		{
			$phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, 1);
			$phpCell->setValue($fieldName);
		}
		
		foreach($cards as $row_index => $card)
		{
			$col_index = 0;
			foreach($associationMappings as $fieldName => $associationMapping)
			{
				if($associationMapping['isOwningSide']) {
					$getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$fieldName")));
					$value = $card->$getter() ? $card->$getter()->getName() : '';
					
					$phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, $row_index+2);
					$phpCell->setValue($value);
				}
			}
			foreach($fieldNames as $fieldName)
			{
				$getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$fieldName")));
				$value = $card->$getter() ?: '';

				$phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, $row_index+2);
				if($fieldName == 'code')
				{
					$phpCell->setValueExplicit($value, 's');
				}
				else
				{
					$phpCell->setValue($value);
				}
			}
		}
		
		$writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
		$response = $this->get('phpexcel')->createStreamedResponse($writer);
		$response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
		$response->headers->set('Content-Disposition', 'attachment;filename='.$pack->getName().'.xlsx');
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
		return $response;
	}
	
    public function uploadFormAction()
    {
        return $this->render('AppBundle:Excel:upload_form.html.twig');
    }
    
    public function uploadAction(Request $request)
    {
        /* @var $uploadedFile \Symfony\Component\HttpFoundation\File\UploadedFile */
        $uploadedFile = $request->files->get('upfile');
        $inputFileName = $uploadedFile->getPathname();
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($inputFileName);
        $objWorksheet  = $objPHPExcel->getActiveSheet();

        $enableCardCreation = $request->request->has('create');
        $enableUniversalFields = $request->request->has('universal');
        
        $cards = array();
        $firstRow = true;
        foreach($objWorksheet ->getRowIterator() as $row)
        {
            // dismiss first row (titles)
            if($firstRow)
            {
                $firstRow = false;
                continue;
            }
            
            $card = array();
            $specificFields = array('A', 'E', 'H', 'I', 'V');
            
            $cellIterator = $row->getCellIterator();
            foreach ($cellIterator as $cell) {
                $c = $cell->getColumn();
                if(!$enableUniversalFields && !in_array($c, $specificFields)) continue;
                // A:code // E:name // H:keywords // I:text // V:flavor
                switch($c)
                {
                	case 'A': $card['code'] = $cell->getValue(); break;
                	case 'B': $card['pack'] = $cell->getValue(); break;
                	case 'C': $card['number'] = $cell->getValue(); break;
                	case 'D': $card['uniqueness'] = $cell->getValue(); break;
                	case 'E': $card['title'] = $cell->getValue(); break;
                	case 'F': $card['cost'] = $cell->getValue(); break;
                	case 'G': $card['type'] = $cell->getValue(); break;
                	case 'H': $card['keywords'] = $cell->getValue(); break;
                	case 'I': $card['text'] = str_replace("\n", "\r\n", $cell->getValue()); break;
                	case 'J': $card['side'] = $cell->getValue(); break;
                	case 'K': $card['faction'] = $cell->getValue(); break;
                	case 'L': $card['factionCost'] = $cell->getValue(); break;
                	case 'M': $card['strength'] = $cell->getValue(); break;
                	case 'N': $card['trashCost'] = $cell->getValue(); break;
                	case 'O': $card['memoryUnits'] = $cell->getValue(); break;
                	case 'P': $card['advancementCost'] = $cell->getValue(); break;
                	case 'Q': $card['agendaPoints'] = $cell->getValue(); break;
                	case 'R': $card['minimumDeckSize'] = $cell->getValue(); break;
                	case 'S': $card['influenceLimit'] = $cell->getValue(); break;
                	case 'T': $card['baseLink'] = $cell->getValue(); break;
                	case 'U': $card['illustrator'] = $cell->getValue(); break;
                	case 'V': $card['flavor'] = $cell->getValue(); break;
                	case 'W': $card['quantity'] = $cell->getValue(); break;
                	case 'X': $card['limited'] = $cell->getValue(); break;
                }
                
            }
            if(count($card) && !empty($card['code'])) $cards[] = $card;
        }
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle\Entity\Card');
        
        $counter = 0;
        foreach($cards as $card)
        {
        	/* @var $dbcard \AppBundle\Entity\Card */
        	$dbcard = $repo->findOneBy(array('code' => $card['code']));
        	if(!$dbcard) {
        		if($enableCardCreation) {
        			$dbcard = new Card();
        			$dbcard->setTs(new \DateTime());
        		} else {
        			continue;
        		}
        	}
        
        	if(isset($card['pack'])) {
        		$card['pack'] = $em->getRepository('AppBundle:Pack')->findOneBy(array("name$loc" => $card['pack']));
        		if(!$card['pack']) continue;
        	}

        	if(isset($card['type'])) {
        		$card['type'] = $em->getRepository('AppBundle:Type')->findOneBy(array("name$loc" => $card['type']));
        		if(!$card['type']) continue;
        	}
        
        	if(isset($card['side'])) {
        		$card['side'] = $em->getRepository('AppBundle:Side')->findOneBy(array("name$loc" => $card['side']));
        		if(!$card['side']) continue;
        	}
        
        	if(isset($card['faction'])) {
        		$card['faction'] = $em->getRepository('AppBundle:Faction')->findOneBy(array("name$loc" => $card['faction'], "side" => $card['side']));
        		if(!$card['faction']) continue;
        	}
        
        	foreach($card as $key => $value) {
        		$func = 'set'.ucfirst($key);
        		$dbcard->$func($value);
        	}
        
        	$em->persist($dbcard);
        	$counter++;
        }
        $em->flush();
        
        return new Response($counter." card changed");
    }
}