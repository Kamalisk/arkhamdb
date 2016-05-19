<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Cycle;
use AppBundle\Entity\Pack;
use AppBundle\Entity\Card;

class ImportJsonCommand extends ContainerAwareCommand
{
	/* @var $em EntityManager */
	private $em;

	/* @var $output OutputInterface */
	private $output;
	
	private $collections = [];

	protected function configure()
	{
		$this
		->setName('app:import:json')
		->setDescription('Import cards data file in json format from a copy of https://github.com/Alsciende/thronesdb-json-data')
		->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Path to the repository'
				)
		
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path = $input->getArgument('path');
		$this->em = $this->getContainer()->get('doctrine')->getEntityManager();
		$this->output = $output;

		/* @var $helper \Symfony\Component\Console\Helper\QuestionHelper */
		$helper = $this->getHelper('question');
		
		$this->loadCollection('Type');
		$this->loadCollection('Faction');
		$this->loadCollection('Pack');
		$this->loadCollection('Cycle');
		
		// first, cycles

		$cyclesFileInfo = $this->getFileInfo($path, 'cycles.json');
		$this->importCyclesJsonFile($cyclesFileInfo);
		$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
		if(!$helper->ask($input, $output, $question)) {
			die();
		}
		$this->em->flush();
		$this->loadCollection('Cycle');
		
		// second, packs

		$packsFileInfo = $this->getFileInfo($path, 'packs.json');
		$this->importPacksJsonFile($packsFileInfo);
		$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
		if(!$helper->ask($input, $output, $question)) {
			die();
		}
		$this->em->flush();
				$this->loadCollection('Pack');
		
		// third, cards
		
		$fileSystemIterator = $this->getFileSystemIterator($path);
		
		foreach ($fileSystemIterator as $fileinfo) {
			$this->importCardsJsonFile($fileinfo);
		}
		$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
		if(!$helper->ask($input, $output, $question)) {
			die();
		}
		$this->em->flush();
	}

	protected function importCyclesJsonFile(\SplFileInfo $fileinfo)
	{
		$cyclesData = $this->getDataFromFile($fileinfo);
		foreach($cyclesData as $cycleData) {
			$cycle = $this->getEntityFromData('AppBundle\Entity\Cycle', $cycleData, [
					'code', 
					'name', 
					'position', 
					'size'
			], [], []);
			$this->em->persist($cycle);
		}
	}

	protected function importPacksJsonFile(\SplFileInfo $fileinfo)
	{
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
			], []);
			$this->em->persist($pack);
		}
	}
	
	protected function importCardsJsonFile(\SplFileInfo $fileinfo)
	{
		$code = $fileinfo->getBasename('.json');
		
		$pack = $this->em->getRepository('AppBundle:Pack')->findOneBy(['code' => $code]);
		if(!$pack) throw new \Exception("Unable to find Pack [$code]");
		
		$cardsData = $this->getDataFromFile($fileinfo);
		foreach($cardsData as $cardData) {
			$card = $this->getEntityFromData('AppBundle\Entity\Card', $cardData, [
					'code',
					'deck_limit',
					'position',
					'quantity',
					'name',
					'is_loyal',
					'is_unique'
			], [
					'faction_code',
					'pack_code',
					'type_code'
			], [
					'illustrator',
					'flavor',
					'traits',
					'text',
					'cost',
					'octgn_id'
			]);
			$this->em->persist($card);
		}
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
			$this->output->writeln("Changing the <info>$fieldName</info> of <info>".$entity->toString()."</info> ($currentJsonValue => $newJsonValue)");
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
		
		if(!key_exists($key, $metadata->fieldNames)) {
			throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
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
			$entity = new $entityName();
		}
	
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}

		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, FALSE);
		}
		
		foreach($foreignKeys as $key) {
			$foreignEntityShortName = ucfirst(str_replace('_code', '', $key));
	
			if(!key_exists($key, $data)) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}

			$foreignCode = $data[$key];
			if(!key_exists($foreignEntityShortName, $this->collections)) {
				throw new \Exception("No collection for [$foreignEntityShortName] in ".json_encode($data));
			}
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
			// calling a function whose name depends on the type_code
			$functionName = 'import' . $entity->getType()->getName() . 'Data';
			$this->$functionName($entity, $data, $entityName);
		}
	
		return $entity;
	}
	
	protected function importAgendaData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
		];
		
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}
	}

	protected function importAttachmentData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
				'cost'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}
	}

	protected function importCharacterData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
				'cost',
				'strength',
				'is_military',
				'is_intrigue',
				'is_power'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}
	}

	protected function importEventData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
				'cost'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}
	}

	protected function importLocationData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
				'cost'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}
	}

	protected function importPlotData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
				'claim',
				'income',
				'initiative',
				'reserve'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}
	}

	protected function importTitleData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}
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
		
		$directory = 'pack';
		
		if(!$fs->exists("$path/$directory")) {
			throw new \Exception("No '$directory' directory found at [$path]");
		}
		
		$iterator = new \GlobIterator("$path/$directory/*.json");
		
		if(!$iterator->count()) {
			throw new \Exception("No json file found at [$path/set]");
		}
		
		return $iterator;
	}
	
	protected function loadCollection($entityShortName)
	{
		$this->collections[$entityShortName] = [];

		$entities = $this->em->getRepository('AppBundle:'.$entityShortName)->findAll();
		
		foreach($entities as $entity) {
			$this->collections[$entityShortName][$entity->getCode()] = $entity;
		}
	}
	
}