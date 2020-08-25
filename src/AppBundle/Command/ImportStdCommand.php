<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Cycle;
use AppBundle\Entity\Pack;
use AppBundle\Entity\Card;

class ImportStdCommand extends ContainerAwareCommand
{
	/* @var $em EntityManager */
	private $em;

	private $links = [];
	private $bonds = [];

	/* @var $output OutputInterface */
	private $output;
	
	private $collections = [];

	protected function configure()
	{
		$this
		->setName('app:import:std')
		->setDescription('Import cards data file in json format from a copy of https://github.com/Kamalisk/arkhamdb-json-data')
		->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Path to the repository'
				);

        	$this->addOption(
                        'player',
                        null,
                        InputOption::VALUE_NONE,
                        'Only player cards'
	        );
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		ini_set('memory_limit', '2G');
		$path = $input->getArgument('path');
		$player_only = $input->getOption('player');
		$this->em = $this->getContainer()->get('doctrine')->getEntityManager();
		$this->output = $output;

		/* @var $helper \Symfony\Component\Console\Helper\QuestionHelper */
		$helper = $this->getHelper('question');
		//$this->loadCollection('Card');
		// factions
		
		$output->writeln("Importing Classes...");
		$factionsFileInfo = $this->getFileInfo($path, 'factions.json');
		$imported = $this->importFactionsJsonFile($factionsFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Faction');
		$this->collections['Faction2'] = $this->collections['Faction'];
		$output->writeln("Done.");
		
		// types
		
		$output->writeln("Importing Types...");
		$typesFileInfo = $this->getFileInfo($path, 'types.json');
		$imported = $this->importTypesJsonFile($typesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Type');
		$output->writeln("Done.");
		
		// subtypes
		
		$output->writeln("Importing SubTypes...");
		$subtypesFileInfo = $this->getFileInfo($path, 'subtypes.json');
		$imported = $this->importSubtypesJsonFile($subtypesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Subtype');
		$output->writeln("Done.");

		// encounter sets
		
		$output->writeln("Importing Encounter Sets...");
		$encounterFileInfo = $this->getFileInfo($path, 'encounters.json');
		$imported = $this->importEncountersJsonFile($encounterFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Encounter');
		$output->writeln("Done.");
		
		

		
		// cycles

		$output->writeln("Importing Taboos...");
		$taboosFileInfo = $this->getFileInfo($path, 'taboos.json');
		$imported = $this->importTaboosJsonFile($taboosFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Taboo');
		$output->writeln("Done.");


		
		// cycles

		$output->writeln("Importing Cycles...");
		$cyclesFileInfo = $this->getFileInfo($path, 'cycles.json');
		$imported = $this->importCyclesJsonFile($cyclesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Cycle');
		$output->writeln("Done.");
		
		
		
		
		// second, packs

		$output->writeln("Importing Packs...");
		$packsFileInfo = $this->getFileInfo($path, 'packs.json');
		$imported = $this->importPacksJsonFile($packsFileInfo);
		$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Pack');
		$output->writeln("Done.");
				
		// third, cards
		
		$output->writeln("Importing Cards...");
		$imported = [];
		// get subdirs of files and do this for each file
		$scanned_directory = array_diff(scandir($path."/pack"), array('..', '.'));
		foreach($scanned_directory as $dir){
			$fileSystemIterator = $this->getFileSystemIterator($path."pack/".$dir);
			foreach ($fileSystemIterator as $fileinfo) {
				$imported = array_merge($imported, $this->importCardsJsonFile($fileinfo, $player_only));
			}
		}
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		// reload the cards so we can link cards
		if ($this->links && count($this->links) > 0) {
			$output->writeln("Resolving Links");
			$this->loadCollection('Card');
			foreach($this->links as $link) {
				$card = $this->em->getRepository('AppBundle\\Entity\\Card')->findOneBy(['code' => $link['card_id']]);
				$target = $this->em->getRepository('AppBundle\\Entity\\Card')->findOneBy(['code' => $link['target_id']]);
				if ($card && $target) {
					if (isset($link['type']) && $link['type'] == 'alternate_of') {
						$card->setAlternateOf($target);
						$target->setAlternateOf();
						$output->writeln("Importing alternate_of between ".$card->getName()." and ".$target->getName().".");
					} else {
						$card->setLinkedTo($target);
						$target->setLinkedTo();
						$output->writeln("Importing link between ".$card->getName()." and ".$target->getName().".");
					}
				}
			}
			$this->em->flush();
		}

		$output->writeln("Done.");
		
	}

	protected function importFactionsJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];
	
		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$faction = $this->getEntityFromData('AppBundle\\Entity\\Faction', $data, [
					'code',
					'name',
					'is_primary'
			], [], []);
			if($faction) {
				$result[] = $faction;
				$this->em->persist($faction);
			}
		}
	
		return $result;
	}
	
	protected function importTypesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];
	
		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Type', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}
	
		return $result;
	}
	
		protected function importSubtypesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];
	
		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Subtype', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}
	
		return $result;
	}

		protected function importEncountersJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];
	
		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Encounter', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}
	
		return $result;
	}


		protected function importScenariosJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];
	
		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Scenario', $data, [
				'code',
				'name'
			], [
				'campaign_code'
			], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}
	
		return $result;
	}
	
			protected function importCampaignsJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];
	
		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Campaign', $data, [
					'code',
					'name',
					'size'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}
	
		return $result;
	}
	
	protected function importCyclesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];
	
		$cyclesData = $this->getDataFromFile($fileinfo);
		foreach($cyclesData as $cycleData) {
			$cycle = $this->getEntityFromData('AppBundle\Entity\Cycle', $cycleData, [
					'code', 
					'name', 
					'position', 
					'size'
			], [], []);
			if($cycle) {
				$result[] = $cycle;
				$this->em->persist($cycle);
			}
		}
		
		return $result;
	}

	protected function importTaboosJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];
	
		$taboosData = $this->getDataFromFile($fileinfo);
		foreach($taboosData as $tabooData) {
			$tabooData['cards'] = json_encode($tabooData['cards']);
			$taboo = $this->getEntityFromData('AppBundle\Entity\Taboo', $tabooData, [
					'code', 
					'name', 
					'date_start', 
					'active',
					'cards'
			], [], []);
			if($taboo) {
				$result[] = $taboo;
				$this->em->persist($taboo);
			}
		}
		
		return $result;
	}


	protected function importPacksJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];
	
		$packsData = $this->getDataFromFile($fileinfo);
		foreach($packsData as $packData) {
			$pack = $this->getEntityFromData('AppBundle\Entity\Pack', $packData, [
					'code', 
					'name', 
					'position', 
					'size', 
					'date_release'
			], [
					'cycle_code'
			], [
					'cgdb_id'
			]);
			if($pack) {
				$result[] = $pack;
				$this->em->persist($pack);
			}
		}
		
		return $result;
	}
	
	protected function importCardsJsonFile(\SplFileInfo $fileinfo, $special="")
	{
		$result = [];
	
		$code = $fileinfo->getBasename('.json');
		if (stristr($code, "_encounter") !== FALSE && $special){
			return $result;
		}
		$code = str_replace("_encounter", "", $code);

		$pack = $this->em->getRepository('AppBundle:Pack')->findOneBy(['code' => $code]);
		if(!$pack) throw new \Exception("Unable to find Pack [$code]");
		
		$cardsData = $this->getDataFromFile($fileinfo);
		foreach($cardsData as $cardData) {
			$card = $this->getEntityFromData('AppBundle\Entity\Card', $cardData, [
					'code',
					'position',
					'quantity',
					'name'
			], [
					'faction_code',
					'faction2_code',
					'pack_code',
					'type_code',
					'subtype_code',
					'encounter_code',
					'back_card_code',
					'front_card_code'
			], [
					'deck_limit',
					'encounter_position',
					'illustrator',
					'flavor',
					'traits',
					'text',
					'cost',
					'skill_willpower',
					'skill_intellect',
					'skill_combat',
					'skill_agility',
					'skill_wild',
					'health',
					'sanity',
					'restrictions',
					'slot',
					'deck_options',
					'deck_requirements',
					'subname',
					'bonded_to',
					'bonded_count',
					'xp',
					'enemy_evade',
					'enemy_fight',
					'vengeance',
					'victory',
					'enemy_damage',
					'enemy_horror',
					'doom',
					'clues',
					'shroud',
					'back_text',
					'back_flavor',
					'back_name',
					'double_sided',
					'stage',
					'is_unique',
					'health_per_investigator',
					'clues_fixed',
					'hidden',
					'permanent',
					'exile',
					'exceptional',
					'myriad'

			]);
			if($card) {
				$result[] = $card;
				$this->em->persist($card);
			}
		}
		
		return $result;
	}
	
	protected function copyFieldValueToEntity($entity, $entityName, $fieldName, $newJsonValue)
	{
		$metadata = $this->em->getClassMetadata($entityName);
		$type = $metadata->fieldMappings[$fieldName]['type'];
		
		// new value, by default what json gave us is the correct typed value
		$newTypedValue = $newJsonValue;
		
		// current value, by default the json, serialized value is the same as what's in the entity
		$getter = 'get'.ucfirst($fieldName);
		$currentJsonValue = $currentTypedValue = $entity->$getter();

		// if the field is a data, the default assumptions above are wrong
		if(in_array($type, ['date', 'datetime'])) {
			if($newJsonValue !== null) {
				$newTypedValue = new \DateTime($newJsonValue);
			}
			if($currentTypedValue !== null) {
				switch($type) {
					case 'date': {
						$currentJsonValue = $currentTypedValue->format('Y-m-d');
						break;
					}
					case 'datetime': {
						$currentJsonValue = $currentTypedValue->format('Y-m-d H:i:s');
					}
				}
			}
		}
		
		$different = ($currentJsonValue !== $newJsonValue);
		if($different) {
			//print_r(gettype($currentJsonValue));
			//print_r(gettype($newJsonValue));
			if (is_array($currentJsonValue) || is_array($newJsonValue)){
				$this->output->writeln("Changing the <info>$fieldName</info> of <info>".$entity->toString()."</info>");
			} else {
				$this->output->writeln("Changing the <info>$fieldName</info> of <info>".$entity->toString()."</info> ($currentJsonValue => $newJsonValue)");
			}
			$setter = 'set'.ucfirst($fieldName);
			$entity->$setter($newTypedValue);
		}
	}
	
	protected function copyKeyToEntity($entity, $entityName, $data, $key, $isMandatory = TRUE)
	{
		$metadata = $this->em->getClassMetadata($entityName);
		if(!key_exists($key, $data)) {
			if($isMandatory) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			} else {
				$data[$key] = null;
			}
		}
		
		$value = $data[$key];
		if ($key == "is_unique"){
			if (!$value){
				$value = false;
			}
		}
		if ($key == "hidden"){
			if (!$value){
				$value = false;
			}
		}
		if ($key == "permanent"){
			if (!$value){
				$value = false;
			}
		}
		if ($key == "exceptional"){
			if (!$value){
				$value = false;
			}
		}
		
		if ($key == "deck_options"){
			if ($value){
				$value = json_encode($value);
			}
		}
		
		if ($key == "deck_options" && $value){
			//print_r($value);
		}
		
		if ($key == "health_per_investigator" || $key == "is_unique"){
			if ($value){
				//echo $key." ".$value."\n";
			}
		}
		if(!key_exists($key, $metadata->fieldNames)) {
			throw new \Exception("Missing column [$key] in entity ".$entityName);
		}
		$fieldName = $metadata->fieldNames[$key];
		
		$this->copyFieldValueToEntity($entity, $entityName, $fieldName, $value);
	}

	protected function getEntityFromData($entityName, $data, $mandatoryKeys, $foreignKeys, $optionalKeys)
	{
		if(!key_exists('code', $data)) {
			throw new \Exception("Missing key [code] in ".json_encode($data));
		}
		
		$entity = $this->em->getRepository($entityName)->findOneBy(['code' => $data['code']]);

		if(!$entity) {
			// if we cant find it, try more complex methods just to check
			// the only time this should work is if the existing name also has an _ meaning it was temporary. 
			
			if ($entityName == "AppBundle\Entity\Card"){
				
				if (isset($data['xp'])){
					$entity = $this->em->getRepository($entityName)->findOneBy(['name' => $data['name'], 'type'=> $data['type_code'], 'xp' => $data['xp']]);				
				}else {
					$entity = $this->em->getRepository($entityName)->findOneBy(['name' => $data['name'], 'type'=> $data['type_code'], 'xp' => null]);
				}
			}
			
			if (!$entity){
				$entity = new $entityName();
			}			
		}
		$orig = $entity->serialize();
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}

		foreach($optionalKeys as $key) {			
			$this->copyKeyToEntity($entity, $entityName, $data, $key, FALSE);
		}
		
		foreach($foreignKeys as $key) {
			$foreignEntityShortName = ucfirst(str_replace('_code', '', $key));
			if ($key === "front_card_code"){
				$foreignEntityShortName = "Card";
			}

			if(!key_exists($key, $data)) {
				// optional links to other tables 
				if ($key === "faction2_code" || $key === "subtype_code" || $key === "encounter_code" || $key === "back_card_code" || $key === "front_card_code"){
					continue;
				}
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}
			
			$foreignCode = $data[$key];
			if(!key_exists($foreignEntityShortName, $this->collections)) {
				throw new \Exception("No collection for [$foreignEntityShortName] in ".json_encode($data));
			}

			if (!$foreignCode){
				continue;
			} 
			//echo "\n";
			//print("hvor mange ".count($this->collections[$foreignEntityShortName]));
			if(!key_exists($foreignCode, $this->collections[$foreignEntityShortName])) {
				throw new \Exception("Invalid code [$foreignCode] for key [$key] in ".json_encode($data));
			}
			$foreignEntity = $this->collections[$foreignEntityShortName][$foreignCode];
	
			$getter = 'get'.$foreignEntityShortName;
			
			if(!$entity->$getter() || $entity->$getter()->getId() !== $foreignEntity->getId()) {
				$this->output->writeln("Changing the <info>$key</info> of <info>".$entity->toString()."</info>");
				$setter = 'set'.$foreignEntityShortName;
				$entity->$setter($foreignEntity);
			}
		}

		// special case for Card
		if($entityName === 'AppBundle\Entity\Card') {
			if ($entity->getName()){
				$entity->setRealName($entity->getName());
			}
			if ($entity->getTraits()){
				$entity->setRealTraits($entity->getTraits());
			}
			if ($entity->getText()){
				$entity->setRealText($entity->getText());
			}
			if ($entity->getSlot()){
				$entity->setRealSlot($entity->getSlot());
			}

			if (isset($data['back_link'])){
				// if we have back link, store the reference here
				$this->links[] = ['card_id'=> $entity->getCode(), 'target_id'=> $data['back_link']];
			}
			if (isset($data['alternate_of'])){
				// if we have back link, store the reference here
				$this->links[] = ['card_id'=> $entity->getCode(), 'target_id'=> $data['alternate_of'], 'type' => 'alternate_of'];
			}
			// calling a function whose name depends on the type_code
			$functionName = 'import' . $entity->getType()->getName() . 'Data';
			$this->$functionName($entity, $data);
		}

		if ($entity->serialize() !== $orig) {
			return $entity;
		}
		if (isset($data['back_link']) && $entity->getLinkedTo() && $entity->getLinkedTo()->getCode() !== $data['back_link']){
			return $entity;
		}
		if (isset($data['alternate_of']) && $entity->getAlternateOf() && $entity->getAlternateOf()->getCode() !== $data['alternate_of']){
			return $entity;
		}
	}

	protected function importAssetData(Card $card, $data)
	{

	}

	protected function importInvestigatorData(Card $card, $data)
	{
		$mandatoryKeys = [
				'skill_willpower',
				'skill_intellect',
				'skill_combat',
				'skill_agility',
				'health',
				'sanity'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}

	protected function importEnemyData(Card $card, $data)
	{
		$mandatoryKeys = [
				'enemy_fight',
				'enemy_evade',
				'health'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}


	protected function importAgendaData(Card $card, $data)
	{
		$mandatoryKeys = [
				'doom'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}
	
	protected function importStoryData(Card $card, $data)
	{

	}
	

	protected function importActData(Card $card, $data)
	{
		$mandatoryKeys = [
				'clues'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}

	protected function importLocationData(Card $card, $data)
	{
		$mandatoryKeys = [
				'shroud',
				'clues'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}


	protected function importEventData(Card $card, $data)
	{
		$mandatoryKeys = [
				//'cost'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}

	protected function importSkillData(Card $card, $data)
	{

	}

	protected function importAdventureData(Card $card, $data)
	{

	}
	protected function importScenarioData(Card $card, $data)
	{

	}

	protected function importTreacheryData(Card $card, $data)
	{

	}

	protected function getDataFromFile(\SplFileInfo $fileinfo)
	{
	
		$file = $fileinfo->openFile('r');
		$file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
	
		$lines = [];
		foreach($file as $line) {
			if($line !== false) $lines[] = $line;
		}
		$content = implode('', $lines);
	
		$data = json_decode($content, true);
	
		if($data === null) {
			throw new \Exception("File [".$fileinfo->getPathname()."] contains incorrect JSON (error code ".json_last_error().")");
		}
	
		return $data;
	}
	
	protected function getFileInfo($path, $filename)
	{
		$fs = new Filesystem();
		
		if(!$fs->exists($path)) {
			throw new \Exception("No repository found at [$path]");
		}
		
		$filepath = "$path/$filename";
		
		if(!$fs->exists($filepath)) {
			throw new \Exception("No $filename file found at [$path]");
		}
		
		return new \SplFileInfo($filepath);
	}
	
	protected function getFileSystemIterator($path)
	{
		$fs = new Filesystem();
		
		if(!$fs->exists($path)) {
			throw new \Exception("No repository found at [$path]");
		}

		$iterator = new \GlobIterator("$path/*.json");

		if(!$iterator->count()) {
			throw new \Exception("No json file found at [$path/set]");
		}
		
		return $iterator;
	}
	
	protected function loadCollection($entityShortName)
	{
		$this->collections[$entityShortName] = [];

		$entities = $this->em->getRepository('AppBundle:'.$entityShortName)->findAll();
		//echo $entityShortName."\n";
		foreach($entities as $entity) {
			$this->collections[$entityShortName][$entity->getCode()] = $entity;
			//echo $entity->getCode()."\n";
		}
	}
	
}
