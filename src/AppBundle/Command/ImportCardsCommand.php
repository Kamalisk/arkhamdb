<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Entity\Card;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ImportCardsCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
        ->setName('app:import:cards')
        ->setDescription('Download new card data from CGDB')
        ->addArgument(
        		'filename',
        		InputArgument::REQUIRED,
        		'Name of the file to download (ex "GT03-db")'
        )
        ->addOption(
        		'yes',
        		'y',
        		InputOption::VALUE_NONE,
        		'Reply yes to all questions'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /* @var $helper \Symfony\Component\Console\Helper\QuestionHelper */
        $helper = $this->getHelper('question');

        $assets_helper = $this->getContainer()->get('templating.helper.assets');
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        
        /* @var $allFactions \AppBundle\Entity\Faction[] */
        $allFactions = $em->getRepository('AppBundle:Faction')->findAll();
        
        /* @var $allTypes \AppBundle\Entity\Type[] */
        $allTypes = $em->getRepository('AppBundle:Type')->findAll();
        
        $filename = $input->getArgument('filename');
        
        $assumeYes = $input->getOption('yes'); 
        
        $fileUrl = "http://www.cardgamedb.com/deckbuilders/gameofthrones2ndedition/database/$filename.jgz";
        $output->writeln("Trying to download the file...");
        $file = file_get_contents($fileUrl);
        if(!preg_match('/^cards = (.*);$/', $file, $matches)) {
          $output->writeln("<error>Error while parsing js file</error>");
          return;
        }
        $output->writeln("File successfully downloaded");

        $json = $matches[1];
        $lookup = json_decode($json, TRUE);
        $output->writeln(count($lookup)." cards found in file");
        
        foreach($lookup as $data) {
          
          $name = $data['name'];
          $name = str_replace(['“', '”', '’'], ['"', '"', '\''], $name);
          
          $traits = $data['traits'];
          $traits = str_replace(['“', '”', '’'], ['"', '"', '\''], $traits);
          
          $setname = html_entity_decode($data['setname'], ENT_QUOTES);
          $setname = str_replace(['“', '”', '’'], ['"', '"', '\''], $setname);
          
          /* @var $pack \AppBundle\Entity\Pack */
          $pack = $em->getRepository('AppBundle:Pack')->findOneBy(array('name' => $setname));
          if(!$pack)
          {
          	$output->writeln("<error>Cannot find pack [".$setname."]</error>");
          	die();
          }
          if($pack->getSize() === count($pack->getCards())) {
          	// shortcut: we already know all the cards of this pack
          	continue;
          }
          
          /* @var $card \AppBundle\Entity\Card */
          $card = $em->getRepository('AppBundle:Card')->findOneBy(array('name' => $name, 'pack' => $pack));
          if($card && $card->getOctgnId()) continue;
          
          if(!$assumeYes) {
          	$question = new ConfirmationQuestion("Shall I import the card <comment>$name</comment> from the set <comment>$setname</comment>? (Y/n) ", true);
          	if(!$helper->ask($input, $output, $question)) {
          		continue;
          	}
          }
          
          $faction = null;
          foreach($allFactions as $oneFaction)
          {
          	if($data[$oneFaction->getCode()] === 'Y')
          	{
          		$faction = $oneFaction;
          	}
          }
          if(!$faction) {
          	$output->writeln("<error>Cannot find faction for this card</error>");
          	dump($data);
          	die();
          }
          
          $type = null;
          foreach($allTypes as $oneType)
          {
          	if($data['type'] === $oneType->getName())
          	{
          		$type = $oneType;
          	}
          }
          if(!$type) {
          	$output->writeln("<error>Cannot find type for this card</error>");
          	dump($data);
          	die();
          }
          
          $position = intval($data['num']);
          
          $text = $data['text'];
          $text = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $text);
          $text = str_replace(['<br />'], ["\n"], $text);
          $text = preg_replace("/<strong class='bbc'>([^<]+)<\/strong>/", "<b>\\1</b>", $text);
          $text = str_replace("</b>: ", ":</b> ", $text);
          $text = preg_replace("/<em class='bbc'><b>([^<]+)<\/b><\/em>/", "<i>\\1</i>", $text);
          $text = preg_replace("/<em class='bbc'>([^<]+)<\/em>/", "", $text);
          $text = preg_replace_callback("/\[(.*?)\]/", function ($matches) use ($allFactions) {
          	$token = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $matches[1]);
          	foreach($allFactions as $faction) {
          		if($faction->getName() === $token || ($faction->getName() === "The Night's Watch" && $token === "Night's Watch")) {
          			return '['.$faction->getCode().']';
          		}
          	}
          	return '['.strtolower($token).']';
          }, $text);
          $text = preg_replace("/Plot deck limit: \d./", "", $text);
          $text = preg_replace("/Deck Limit: \d./", "", $text);
          $text = preg_replace("/ +/", " ", $text);
          $text = preg_replace("/\n+/", "\n", $text);
          $text = trim($text);
          
          if(!$card) $card = new Card();
          $card->setClaim($data['claim'] !== '' ? $data['claim'] : null);
          $card->setCode(sprintf("%02d%03d", $pack->getCycle()->getPosition(), $position));
          $card->setCost($data['cost'] !== '' && $data['cost'] !== 'X' ? $data['cost'] : null);
          $card->setDeckLimit($data['max']);
          $card->setFaction($faction);
          $card->setIllustrator(trim($data['illustrator']));
          $card->setIncome($data['gold'] !== '' ? $data['gold'] : null);
          $card->setInitiative($data['initiative'] !== '' ? $data['initiative'] : null);
          $card->setIsIntrigue($data['intrigue'] === 'Y');
          $card->setIsLoyal($data['loyal'] === 'L');
          $card->setIsMilitary($data['military'] === 'Y');
          $card->setIsPower($data['power'] === 'Y');
          $card->setIsUnique($data['unique'] === 'Y');
          $card->setName($name);
          $card->setPack($pack);
          $card->setPosition($position);
          $card->setQuantity(3); // it looks like $data['quantity'] is wrong
          $card->setReserve($data['reserve'] !== '' ? $data['reserve'] : null);
          $card->setStrength($data['strength'] !== '' ? $data['strength'] : null);
          $card->setText($text);
          $card->setTraits($traits);
          $card->setType($type);

          $em->persist($card);
          
          // trying to download image file
          $card_code = $card->getCode();
          $imageurl = $assets_helper->getUrl('bundles/cards/'.$card->getCode().'.png');
          $imagepath= $rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
          $dirname  = dirname($imagepath);
          $outputfile = $dirname . DIRECTORY_SEPARATOR . $card->getCode() . ".jpg";
          
          $cgdburl = "http://lcg-cdn.fantasyflightgames.com/got2nd/" . $data['img'];
          $image = file_get_contents($cgdburl);
          file_put_contents($outputfile, $image);
          
          $output->writeln("Added card ".$card->getName());
        }
        
        $em->flush();
        $output->writeln("Done.");
    }
}
