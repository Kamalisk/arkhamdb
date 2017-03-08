<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ImportImagesCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
        ->setName('app:import:images')
        ->setDescription('Download missing card images from FFG websites')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $assets_helper = $this->getContainer()->get('templating.helper.assets');

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /* @var $repo \AppBundle\Entity\ReviewRepository */
        $repo = $em->getRepository('AppBundle:Card');

        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $output->writeln($rootDir);

        $cards = $repo->findBy([], ['code' => 'ASC']);
        foreach($cards as $card) {
          $card_code = $card->getCode();
          $imageurl = $assets_helper->getUrl('bundles/cards/'.$card_code.'.png');
          $imageurl2 = $assets_helper->getUrl('bundles/cards/'.$card_code.'.jpg');
          $imagepath = $rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
          $imagepath2 = $rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl2);
          
          $imageurl_back = $assets_helper->getUrl('bundles/cards/'.$card_code.'b.png');
          $imageurl2_back = $assets_helper->getUrl('bundles/cards/'.$card_code.'b.jpg');
          $imagepath_back = $rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl_back);
          $imagepath2_back = $rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl2_back);
          $pack_id = $card->getPack()->getId();
          if ($card->getPack()->getCode() == "tmm"){
          	$pack_id = 3;
          } else {
          	continue;
          }
          if(file_exists($imagepath) || file_exists($imagepath2)) {
            $output->writeln("Skip ".$card_code);
          }
          else {
          		// AHC01_121a.jpg
          		echo $card->getPack()->getName()." ".$card->getPack()->getId()."\n";          		
          		if ($card->getType()->getCode() == "location"){
              	$cgdbfile = sprintf('AHC%02d_%db.jpg', $pack_id, $card->getPosition());
              }else {
              	$cgdbfile = sprintf('AHC%02d_%d.jpg', $pack_id, $card->getPosition());
              }
              
              $cgdburl = "http://lcg-cdn.fantasyflightgames.com/ahlcg/" . $cgdbfile;

              $dirname = dirname($imagepath);
              $outputfile = $dirname . DIRECTORY_SEPARATOR . $card_code . ".jpg";

              $image = @file_get_contents($cgdburl);
              if($image !== FALSE) {
                file_put_contents($outputfile, $image);
                $output->writeln("New file at $outputfile");
              }else {
              	$output->writeln("NO Image for $card_code");
              }
           }
           
          if ($card->getDoubleSided()){
	          if(file_exists($imagepath_back) || file_exists($imagepath2_back)) {
							$output->writeln("Skip back".$card_code);
						} else {
	              if ($card->getType()->getCode() == "location"){
	            		$cgdbfile = sprintf('AHC%02d_%d.jpg', $pack_id, $card->getPosition());
	            	}else {
	            		$cgdbfile = sprintf('AHC%02d_%db.jpg', $pack_id, $card->getPosition());
	            	}
	            	$cgdburl = "http://lcg-cdn.fantasyflightgames.com/ahlcg/" . $cgdbfile;
	            	echo $cgdburl;
	            	$image = @file_get_contents($cgdburl);
	            	if($image !== FALSE) {
	            		$dirname = dirname($imagepath);
	            		$outputfile = $dirname . DIRECTORY_SEPARATOR . $card_code . "b.jpg";
	                file_put_contents($outputfile, $image);
	                $output->writeln("New file at $outputfile");
	              }else {
	              	$output->writeln("NO back Image for $card_code");
	              }

	          }
	         }

        }

    }
}
