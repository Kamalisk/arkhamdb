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
		$logfile = fopen("missing.txt", "w") or die("Unable to open file!");
		$cards = $repo->findBy([], ['code' => 'ASC']);
		
		$backlinksCache = [];
		
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

			if ($card->getLinkedTo()){
				$backlinksCache[$card->getLinkedTo()->getCode()] = $card->getLinkedTo()->getCode();
			}

			if(file_exists($imagepath) || file_exists($imagepath2)) {
				$output->writeln("Skip ".$card_code);
				continue;
			}
			fwrite($logfile, $card_code." - ".$card->getName().": ");
			// if we know the cgdb pack id then import it
			if ($card->getPack()->getCgdbId()){
				$pack_id = $card->getPack()->getCgdbId();
			} else {
				fwrite($logfile, $card_code.": No CGDB ID\n");
				continue;
			}
			// AHC01_121a.jpg
			echo $card->getPack()->getName()." ".$card->getPack()->getId()."\n";
			if ($card->getType()->getCode() == "location" && $card->getDoubleSided()){
				$cgdbfile = sprintf('AHC%02d_%db.jpg', $pack_id, $card->getPosition());
			} else if (isset($backlinksCache[$card->getCode()])) {
				$cgdbfile = sprintf('AHC%02d_%db.jpg', $pack_id, $card->getPosition());
			} else {
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
				$cgdbfile = sprintf('AHC%02d_%da.jpg', $pack_id, $card->getPosition());
				$cgdburl2 = "http://lcg-cdn.fantasyflightgames.com/ahlcg/" . $cgdbfile;
				$dirname = dirname($imagepath);
				$outputfile = $dirname . DIRECTORY_SEPARATOR . $card_code . ".jpg";

				$image = @file_get_contents($cgdburl2);
				if($image !== FALSE) {
					file_put_contents($outputfile, $image);
					$output->writeln("New file at $outputfile");
				}else {
					$output->writeln("NO Image for $card_code");
					fwrite($logfile, "no image found");
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
			fwrite($logfile, "\n");
		}
		fclose($logfile);
	}
}
