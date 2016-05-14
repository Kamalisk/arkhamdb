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

	protected function getEntityFromData($entityName, $data, $mandatoryKeys, $foreignKeys, $optionalKeys)
	{
		if(!key_exists('code', $data)) {
			throw new \Exception("Missing key [code] in ".json_encode($data));
		}
	
		$entity = $this->em->getRepository($entityName)->findOneBy(['code' => $data['code']]);
		if(!$entity) {
			$entity = new $entityName();
		}
	
		$metadata = $this->em->getClassMetadata($entityName);

		foreach($mandatoryKeys as $key) {
			if(!key_exists($key, $data)) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}
			$value = $data[$key];
	
			if(!key_exists($key, $metadata->fieldNames)) {
				throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
			}
			$fieldName = $metadata->fieldNames[$key];
			$type = $metadata->fieldMappings[$fieldName]['type'];
			if($type === 'date' && $value !== null) {
				$value = new \DateTime($value);
			}
			
			$getter = 'get'.ucfirst($fieldName);
			$currentValue = $entity->$getter();
			$different = false;
			if(is_object($currentValue) && $currentValue !== null && $value !== null) {
				if($type === 'date') {
					$different = ($currentValue->format('Y-m-d') !== $value->format('Y-m-d'));
				} else {
					$different = ($currentValue->toString() !== $value->toString());
				}
			} else {
				$different = ($currentValue !== $value);
			}
			if($different) {
				$this->output->writeln("Changing the <info>$key</info> of <info> ($currentValue => $value)".$entity->toString()."</info>");
				$setter = 'set'.ucfirst($fieldName);
				$entity->$setter($value);
			}
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
	
		foreach($optionalKeys as $key) {
			if(!key_exists($key, $data)) {
				$data[$key] = null;
			}
	
			$value = $data[$key];

			if(!key_exists($key, $metadata->fieldNames)) {
				throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
			}
			$fieldName = $metadata->fieldNames[$key];
			
			$getter = 'get'.ucfirst($fieldName);
			if($entity->$getter() !== $value) {
				$this->output->writeln("Changing the <info>$key</info> of <info>".$entity->toString()."</info>");
				$setter = 'set'.ucfirst($fieldName);
				$entity->$setter($value);
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
		
		$metadata = $this->em->getClassMetadata($entityName);
		foreach($mandatoryKeys as $key) {
			if(!key_exists($key, $data)) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}
				
			$value = $data[$key];
				
			if(!key_exists($key, $metadata->fieldNames)) {
				throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
			}
			$fieldName = $metadata->fieldNames[$key];
				
			$setter = 'set'.ucfirst($fieldName);
			$card->$setter($value);
		}
	}

	protected function importAttachmentData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
				'cost'
		];

		$metadata = $this->em->getClassMetadata($entityName);
		foreach($mandatoryKeys as $key) {
			if(!key_exists($key, $data)) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}
				
			$value = $data[$key];
			
			if(!key_exists($key, $metadata->fieldNames)) {
				throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
			}
			$fieldName = $metadata->fieldNames[$key];
			
			$setter = 'set'.ucfirst($fieldName);
			$card->$setter($value);
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

		$metadata = $this->em->getClassMetadata($entityName);
		foreach($mandatoryKeys as $key) {
			if(!key_exists($key, $data)) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}
			
			$value = $data[$key];

			if(!key_exists($key, $metadata->fieldNames)) {
				throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
			}
			$fieldName = $metadata->fieldNames[$key];
				
			$setter = 'set'.ucfirst($fieldName);
			$card->$setter($value);
		}
	}

	protected function importEventData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
				'cost'
		];

		$metadata = $this->em->getClassMetadata($entityName);
		foreach($mandatoryKeys as $key) {
			if(!key_exists($key, $data)) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}
				
			$value = $data[$key];

			if(!key_exists($key, $metadata->fieldNames)) {
				throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
			}
			$fieldName = $metadata->fieldNames[$key];
				
			$setter = 'set'.ucfirst($fieldName);
			$card->$setter($value);
		}
	}

	protected function importLocationData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
				'cost'
		];

		$metadata = $this->em->getClassMetadata($entityName);
		foreach($mandatoryKeys as $key) {
			if(!key_exists($key, $data)) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}
				
			$value = $data[$key];
				
			if(!key_exists($key, $metadata->fieldNames)) {
				throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
			}
			$fieldName = $metadata->fieldNames[$key];
				
			$setter = 'set'.ucfirst($fieldName);
			$card->$setter($value);
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
		
		$metadata = $this->em->getClassMetadata($entityName);
		foreach($mandatoryKeys as $key) {
			if(!key_exists($key, $data)) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}
				
			$value = $data[$key];
				
			if(!key_exists($key, $metadata->fieldNames)) {
				throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
			}
			$fieldName = $metadata->fieldNames[$key];
				
			$setter = 'set'.ucfirst($fieldName);
			$card->$setter($value);
		}
	}

	protected function importTitleData(Card $card, $data, $entityName)
	{
		$mandatoryKeys = [
		];

		$metadata = $this->em->getClassMetadata($entityName);
		foreach($mandatoryKeys as $key) {
			if(!key_exists($key, $data)) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}
				
			$value = $data[$key];
				
			if(!key_exists($key, $metadata->fieldNames)) {
				throw new \Exception("Missing key [$key] in fieldNames of ".$entityName);
			}
			$fieldName = $metadata->fieldNames[$key];
				
			$setter = 'set'.ucfirst($fieldName);
			$card->$setter($value);
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