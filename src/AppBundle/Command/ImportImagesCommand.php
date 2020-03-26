<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ImportImagesCommand extends ContainerAwareCommand
{

	protected function get_web_page( $url )
	{
			$options = array(
					CURLOPT_RETURNTRANSFER => true,     // return web page
					CURLOPT_HEADER         => false,    // don't return headers
					CURLOPT_FOLLOWLOCATION => true,     // follow redirects
					CURLOPT_ENCODING       => "",       // handle all encodings
					CURLOPT_USERAGENT      => "spider", // who am i
					CURLOPT_AUTOREFERER    => true,     // set referer on redirect
					CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
					CURLOPT_TIMEOUT        => 120,      // timeout on response
					CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
					CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
			);
	
			$ch      = curl_init( $url );
			curl_setopt_array( $ch, $options );
			$content = curl_exec( $ch );
			$err     = curl_errno( $ch );
			$errmsg  = curl_error( $ch );
			$header  = curl_getinfo( $ch );
			curl_close( $ch );
	
			$header['errno']   = $err;
			$header['errmsg']  = $errmsg;
			$header['content'] = $content;
			return $header;
	}

	protected function configure()
	{
		$this
		->setName('app:import:images')
		->setDescription('Download missing card images from FFG websites')
		->addArgument(
			'article',
			InputArgument::OPTIONAL,
			'Article download'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$article = $input->getArgument('article');

		if ($article) {
			// https://images-cdn.fantasyflightgames.com/filer_public/1b/ea/1bea5697-be8f-4bd1-a1c5-e633de6b19f9/ahc47_nathaniel-cho.png
			// https://www.fantasyflightgames.com/en/news/2020/3/24/your-investigation-begins/
			$request = $this->get_web_page($article);
			//echo $request['content'];
			$article_cards = [];
			preg_match_all("/https\:\/\/images-cdn\.fantasyflightgames\.com\/filer_public\/[0-9a-z][0-9a-z]\/[0-9a-z][0-9a-z]\/[0-9a-z\-]+\/([^\"]*)/", $request['content'], $matches);
			foreach($matches[0] as $index => $match) {
				$url = $match;
				$card = $matches[1][$index];
				$article_cards[$card] = $url;
			}
		}

		$assets_helper = $this->getContainer()->get('assets.packages');

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
			
			if ($article) {
				// ahc50_jacqueline-fine.png
				$cgdbfile = sprintf('ahc%02d_%s.png', $pack_id, strtolower(str_replace(" ", "-", $card->getName())));
				echo $cgdbfile;
			} else {
				if ($card->getType()->getCode() == "location" && $card->getDoubleSided()){
					$cgdbfile = sprintf('AHC%02d_%db.jpg', $pack_id, $card->getPosition());
				} else if (isset($backlinksCache[$card->getCode()])) {
					$cgdbfile = sprintf('AHC%02d_%db.jpg', $pack_id, $card->getPosition());
				} else {
					$cgdbfile = sprintf('AHC%02d_%d.jpg', $pack_id, $card->getPosition());
				}
			}

			if ($article) {
				$cgdburl = "";
				if (isset($article_cards[$cgdbfile])) {
					$cgdburl = $article_cards[$cgdbfile];
				}
			} else {
				$cgdburl = "http://lcg-cdn.fantasyflightgames.com/ahlcg/" . $cgdbfile;
			}

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
