<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Entity\Card;
use AppBundle\Entity\Encounter;
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
        );
        $this->addOption(
        		'yes',
        		'y',
        		InputOption::VALUE_NONE,
        		'Reply yes to all questions'
        );
        $this->addOption(
        		'player',
        		null,
        		InputOption::VALUE_NONE,
        		'Only player cards'
        );
       
        $this->addOption(
        		'encounter',
        		null,
        		InputOption::VALUE_NONE,
        		'Only ecnounter cards'
        )
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	
    		function wtf($t, $allFactions){
        	$text = $t;
          $text = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $text);
          $text = str_replace(['<br />'], ["\n"], $text);
          $text = str_replace('<i>[', "[", $text);
          $text = str_replace(']</i>', "]", $text);
          $text = preg_replace("/<strong class='bbc'>([^<]+)<\/strong>/", "<b>\\1</b>", $text);
          $text = str_replace("<b>[", "[", $text);
          $text = str_replace("]</b>", "]", $text);
          $text = str_replace("- </b>", "</b> -", $text);
          $text = str_replace("</b>: ", ":</b> ", $text);
          $text = str_replace("…", "...", $text);
          $text = str_replace("&ndash;", "-", $text);
          
          $text = str_replace("-&gt;", "→", $text);
          $text = str_replace("&ldquo;", '"', $text);
          $text = str_replace("&rdquo;", '"', $text);
          $text = preg_replace("/<em class='bbc'><b>([^<]+)<\/b><\/em>/", "<i>\\1</i>", $text);
          $text = preg_replace("/<em class='bbc'>([^<]+)<\/em>/", "", $text);
          $text = preg_replace_callback("/\[(.*?)\]/", function ($matches) use ($allFactions) {
          $token = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $matches[1]);
          	foreach($allFactions as $faction) {
          		if($faction->getName() === $token) {
          			return '['.$faction->getCode().']';
          		}
          	}
          	return '['.strtolower($token).']';
          }, $text);
          $text = preg_replace("/Deck Limit: \d./", "", $text);
          $text = preg_replace("/ +/", " ", $text);
          $text = preg_replace("/\n+/", "\n", $text);
          $text = preg_replace("/\[elder sign\]/", "[elder_sign]", $text);
          $text = preg_replace("/\[elder thing\]/", "[elder_thing]", $text);
          $text = preg_replace("/\[Auto-Fail\]/", "[auto_fail]", $text);   
          
        	return $text;
        }
        
    	
    		$card_data = [];
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /* @var $helper \Symfony\Component\Console\Helper\QuestionHelper */
        $helper = $this->getHelper('question');

        $assets_helper = $this->getContainer()->get('templating.helper.assets');
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        
        /* @var $allFactions \AppBundle\Entity\Faction[] */
        $allFactions = $em->getRepository('AppBundle:Faction')->findAll();
        
        /* @var $allFactions \AppBundle\Entity\Encounter[] */
        $allEncounters = $em->getRepository('AppBundle:Encounter')->findAll();
        
        /* @var $allTypes \AppBundle\Entity\Type[] */
        $allTypes = $em->getRepository('AppBundle:Type')->findAll();
        
        $filename = $input->getArgument('filename');
        
        $assumeYes = $input->getOption('yes'); 
        
        $only = "";
        if ($input->getOption('player')){
        	$only = "player";
        }
        if ($input->getOption('encounter')){
        	$only = "encounter";
        }
        
        $fileUrl = "http://www.cardgamedb.com/deckbuilders/arkhamhorror/database/AHC06-db.jgz";
        //$output->writeln("Trying to download the file...");
        $file = file_get_contents($fileUrl);
        if(!preg_match('/^cards = (.*);$/', $file, $matches)) {
          $output->writeln("<error>Error while parsing js file</error>");
          return;
        }
        //$output->writeln("File successfully downloaded");

        $json = $matches[1];
        $lookup = json_decode($json, TRUE);
        //$output->writeln(count($lookup)." cards found in file");
        
        foreach($lookup as $data) {
          
          if ($only == "player" && ($data['deck'] != "P" || $data['encounter'])){
          	continue;
          }
          if ($only == "encounter" && ($data['deck'] == "P" && !$data['encounter']) ){
          	continue;
          }
          
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
          if ($filename != $pack->getCode()){
          	continue;
          }
          if($pack->getSize() === count($pack->getCards())) {
          	// shortcut: we already know all the cards of this pack
          	continue;
          }
          
          if (isset($data['xp'])){
          	//$card = $em->getRepository('AppBundle:Card')->findOneBy(array('name' => $name, 'pack' => $pack, 'xp' => $data['xp']));
          } else {
          	//$card = $em->getRepository('AppBundle:Card')->findOneBy(array('name' => $name, 'pack' => $pack));
          }
          
          /* @var $card \AppBundle\Entity\Card */
          
          //if($card) continue;
          //if($card && $card->getOctgnId()) continue;
          
          if(!$assumeYes) {
          	$question = new ConfirmationQuestion("Shall I import the card <comment>$name</comment> from the set <comment>$setname</comment>? (Y/n) ", true);
          	if(!$helper->ask($input, $output, $question)) {
          		continue;
          	}
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
          
          $encounter = null;
          $faction = null;//$allFactions["mythos"];
          if (!isset($data['clss']) || !$data['clss']){
          	$data['clss'] = "Neutral";
          }
          //echo $data['encounter'];
          if (isset($data['encounter']) && $data['encounter']){
          	if ($data['encounter'] == "Weakness" || $data['encounter'] == "Basic Weakness"){
          		
          	} else {
          		$data['clss'] = "Mythos";
          		if ($type){
          			if ($type->getCode() == "asset" || $type->getCode() == "event" || $type->getCode() == "skill"){
          				$data['clss'] = "Neutral";
          			}
          		}
		          foreach($allEncounters as $oneEncounter)
		          {
		          	if($data['encounter'] === $oneEncounter->getName())
		          	{
		          		$encounter = $oneEncounter;
		          	}
		          }
		          
		          if(!$encounter) {
		          	if(!$encounter) $encounter = new Encounter();
		          	$encounter_name = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', '\'', '\''], $data['encounter']);
		          	$encounter_name = str_replace(" ", "_", $encounter_name);
		          	$encounter_name = str_replace("-", "", $encounter_name);
		          	
		          	// strip ' from encounter code
		          	$encounter_name = str_replace('\'', "", $encounter_name);
		          	$encounter->setCode(strtolower($encounter_name));
		          	$encounter->setName($data['encounter']);
		          	$em->persist($encounter);
		          	$allEncounters[] = $encounter;
		          	//echo $encounter_name;
		          	//$output->writeln("<error>Cannot find encounter for this card</error>");
		          	//dump($data);
		          	//die();
		          }
          	}
          }
          
          foreach($allFactions as $oneFaction)
          {
          	if($data['clss'] === $oneFaction->getName())
          	{
          		$faction = $oneFaction;
          	}
          }
          
          if(!$faction) {
          	$output->writeln("<error>Cannot find faction for this card</error>");
          	dump($data);
          	die();
          }
          
          
          
          
          $position = intval($data['num']);
          
          $text = wtf($data['text'], $allFactions);
          if ($data['textb']){
          	$text_back = wtf($data['textb'], $allFactions);
          }else {
          	$text_back = "";
          }
          
          $card = new Card();
          $card->setCode(sprintf("%02d%03d", $pack->getCycle()->getPosition(), $position));
					if ($data['cost'] == "â€"){
						$data['cost'] = 0;
					}
          $card->setCost($data['cost'] !== '' && $data['cost'] !== 'X' ? $data['cost']+0 : null);

          if ($encounter){
          	$card->setEncounter($encounter);
          }
          if ($data['max']){
          	$card->setDeckLimit($data['max']+0);
          }
          $card->setFaction($faction);
          if (trim($data['illus']) !== "N/A"){
          	$card->setIllustrator(trim($data['illus']));
          }
					if ($data['unique'] === 'Y' ){
          	$card->setIsUnique(true);
        	}
          $card->setName($name);
          $card->setPack($pack);
          $card->setPosition($position+0);
			
					/////////
					/// XXXXXXXXXX
					/*
					1) This data is not extracted properly: 
					1.1) when generating encountercode, ignore ’ and ', and turn '-' into '' 
					1.2) do not create "deck_limit": 0 
					1.3) If cardtype is Asset, force-change faction_code to "neutral" 
					1.5) If cardtype is Location, extract 'text' and 'flavor' as 'back_text' and 'back_flavor, and vice versa 
					1.6) encounter set names ( see card #64; probably uses the <em> tag which the script doesn't know how to process?) 
					1.7) change [auto-fail] to [auto_fail] 2) 
					
					This data is not extracted: 
					2.1) subname 
					2.2) flavor 
					2.3) back_flavor 
					2.4) doom 
					2.5) clues (on Act cards) 
					2.6) shroud 
					2.7) slot (for story assets) 
					
					3) this data is not on cardgamedb, but the script should create fields for it: 
					3.1) encounter_position 
					3.2) "double_sided": true, (if cardtype is Scenario, Agenda, Act, or Location) 
					3.3) stage (if cardtype is Agenda or Act) 
					3.4) back_name (if cardtype is Agenda or Act) == this data is not on cardgamedb, need to add manually: - non-standard double-sided cards - back_name on Locations cards - victory on Locations cards - <cite> 
					
					4) After extracting data, rename: 
					4.1) <i>[ to [ 
					4.2) ]</i> to ] 
					4.2) &ndash; to - 
					4.3) "N/A" to null
					4.4) -&gt; to → 
					4.5) &ldquo; and &rdquo; and “ and ” to \ " (no space) 
					4.6) ’ and &lsquo; and &rsquo; to ' 
					4.7) … to ... (three separate periods) 
					4.8) '- </b>' to '</b> -' (no quotes)
					
					*/
					
          
          $card->setQuantity($data['quantity']+0); // it looks like $data['quantity'] is wrong

          $card->setSkillCombat($data['cmbt'] !== '' && $data['cmbt'] !== '0' ? $data['cmbt']+0 : null);
          $card->setSkillWillpower($data['will'] !== '' && $data['will'] !== '0' ? $data['will']+0 : null);
          $card->setSkillIntellect($data['int'] !== '' && $data['int'] !== '0' ? $data['int']+0 : null);
          $card->setSkillAgility($data['agi'] !== '' && $data['agi'] !== '0' ? $data['agi']+0 : null);
          $card->setSkillWild($data['wild'] !== '' && $data['wild'] !== '0' ? $data['wild']+0 : null);
          $card->setHealth($data['hlth'] !== '' ? $data['hlth']+0 : null);
          $card->setSanity($data['snty'] !== '' ? $data['snty']+0 : null);
          
          $card->setEnemyFight($data['fght'] !== '' ? $data['fght']+0 : null);
          $card->setEnemyEvade($data['evade'] !== '' ? $data['evade']+0 : null);
          $card->setEnemyDamage($data['dmg'] !== '' ? $data['dmg']+0 : null);
          $card->setEnemyHorror($data['horr'] !== '' ? $data['horr']+0 : null);
          
          
          $card->setShroud($data['shrd'] !== '' ? $data['shrd']+0 : null);
          $card->setClues($data['clue'] !== '' ? $data['clue']+0 : null);
        	if ($data['cluet']){
        		$card->setClues($data['cluet']+0);
        	}
          $card->setDoom($data['doomt'] !== '' ? $data['doomt']+0 : null);
          
          if ($data['subtitle']){
          	$card->setSubname($data['subtitle']);
          }
          if ($data['deck'] == "P" && !$data['encounter']){
          	$card->setXp($data['lvl']+0);
          }
          if ($data['vctry']){
          	$card->setVictory($data['vctry'] !== '' ? $data['vctry']+0 : null);
          }
          
        	if ($type->getCode() == "location"){
        		$temp_text = $text;
        		$text = $text_back;
        		$text_back = $temp_text;
        	}
          $card->setText($text);
          if ($text_back){
          	$card->setBackText($text_back);
          	$card->setDoubleSided(true);
          }
          if ($type->getCode() == "location"){
          	$card->setDoubleSided(true);
          }
          if ($data['encounter']){
          	$card->setEncounterPosition("");
          }
          $card->setTraits($traits);
          $card->setType($type);

					// hail mary. scrape data from html page. fuck yeah.
					if ($data['fullurl']){
						$html = file_get_contents($data['fullurl']);
						if ($html){
							$matches = [];
							preg_match("/<div class=\"flavorText\">(.*?)<\/div>/si", $html, $matches);
							if (isset($matches[1]) && $matches[1]){
								$card->setFlavor($matches[1]);
							}
						}
					}

          //print_r($data);
          $card_data[] = $card->serialize();
          //return false;

          
          
          // trying to download image file
          
          //$card_code = $card->getCode();
          //$imageurl = $assets_helper->getUrl('bundles/cards/'.$card->getCode().'.png');
          //$imagepath= $rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
          //$dirname  = dirname($imagepath);
          //$outputfile = $dirname . DIRECTORY_SEPARATOR . $card->getCode() . ".jpg";
          
          //$cgdburl = "http://lcg-cdn.fantasyflightgames.com/got2nd/" . $data['img'];
          //$image = file_get_contents($cgdburl);
          //file_put_contents($outputfile, $image);
          
          //$output->writeln("Added card ".$card->getName());
          
        }
        
        //$em->flush();
        echo json_encode($card_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        //$output->writeln("Done.");
    }
}
