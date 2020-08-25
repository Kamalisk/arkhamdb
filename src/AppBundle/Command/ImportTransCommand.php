<?php
namespace AppBundle\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Cycle;
use AppBundle\Entity\Pack;
use AppBundle\Entity\Card;
use Symfony\Component\Console\Helper\ProgressBar;
class ImportTransCommand extends ContainerAwareCommand
{
	/* @var $em EntityManager */
	private $em;
	/* @var $output OutputInterface */
	private $output;
		
	protected function configure()
	{
		$this
		->setName('app:import:trans')
		->setDescription('Import translation data in json format from a copy of https://github.com/Kamalisk/arkhamdb-json-data')
		->addOption(
				'locale',
				'l',
				InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				"Locale(s) to import"
		)
		->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Path to the repository'
		);
	}
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		ini_set('memory_limit','1024M');
		$this->em = $this->getContainer()->get('doctrine')->getEntityManager();
		$this->output = $output;
		
		$supported_locales = $this->getContainer()->getParameter('supported_locales');
		$default_locale = $this->getContainer()->getParameter('locale');
		$locales = $input->getOption('locale');
		if(empty($locales)) $locales = $supported_locales;
				
		$path = $input->getArgument('path');
		if(substr($path, -1) === '/') {
			$path = substr($path, 0, strlen($path) - 1);
		}
		
		//$things = ['faction', 'type', 'subtype', 'cycle', 'pack', 'campaign', 'scenario', 'encounter'];
		$things = ['faction', 'type', 'subtype', 'cycle', 'pack', 'encounter'];

		foreach($locales as $locale) 
		{
			if($locale === $default_locale) continue;
			$output->writeln("Importing translations for <info>${locale}</info>");
			foreach($things as $thing) 
			{
				$output->writeln("Importing translations for <info>${thing}s</info> in <info>${locale}</info>");
				$fileInfo = $this->getFileInfo("${path}/translations/${locale}", "${thing}s.json");
				$this->importThingsJsonFile($fileInfo, $locale, $thing);
			}
			$this->em->flush();
			
			$fileSystemIterator = $this->getFileSystemIterator("${path}/translations/${locale}");
			
			$output->writeln("Importing translations for <info>cards</info> in <info>${locale}</info>");
			foreach ($fileSystemIterator as $fileInfo) 
			{
				$output->writeln("Importing translations for <info>cards</info> from <info>".$fileInfo->getFilename()."</info>");
				$this->importCardsJsonFile($fileInfo, $locale);
			}
			
			$this->em->flush();
		}
	}

	protected function importThingsJsonFile(\SplFileInfo $fileinfo, $locale, $thing)
	{
		$list = $this->getDataFromFile($fileinfo);

		$progress = new ProgressBar($this->output, count($list));
		$progress->start();

		foreach($list as $data)
		{
			$this->updateEntityFromData($locale, 'AppBundle\\Entity\\'.ucfirst($thing), $data, [
					'code',
					'name'
			], []);

			$progress->advance();
		}
		$progress->finish();
		$progress->clear();
		$this->output->write("\n");
	}

	protected function importCardsJsonFile(\SplFileInfo $fileinfo, $locale)
	{
		$cardsData = $this->getDataFromFile($fileinfo);
		
		$progress = new ProgressBar($this->output, count($cardsData));
		$progress->start();
				
		foreach($cardsData as $cardData) 
		{
			$progress->advance();
			
			$this->updateEntityFromData($locale, 'AppBundle\Entity\Card', $cardData, [
					'code',
					'name'
			], [
					'flavor',
					'traits',
					'text',
					'subname',
					'back_name',
					'back_flavor',
					'back_text',
					'slot'
			]);
		}
		
		$progress->finish();
		$progress->clear();
		$this->output->write("\n");
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
			throw new \Exception("Invalid key [$key] in ".json_encode($data));
		}
		$fieldName = $metadata->fieldNames[$key];
	
		$this->copyFieldValueToEntity($entity, $entityName, $fieldName, $value);
	}
	
	protected function updateEntityFromData($locale, $entityName, $data, $mandatoryKeys, $optionalKeys)
	{
		if(!key_exists('code', $data)) {
			throw new \Exception("Missing key [code] in ".json_encode($data));
		}
	
		# skip empty translations
		if(!isset($data['title']) && !isset($data['name'])) return;
		
		$entity = $this->em->getRepository($entityName)->findOneBy(['code' => $data['code']]);
		if(!$entity) {
			throw new \Exception("Cannot find entity [${data['code']}]");
		}
		$entity->setTranslatableLocale($locale);
		$this->em->refresh($entity);
		
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, FALSE);
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
			throw new \Exception("File [".$fileinfo->getPathname()."] contains incorrect JSON (error code ".json_last_error_msg().")");
		}
	
		return $data;
	}
	
	/**
	 * @return \SplFileInfo
	 */
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
	
	/**
	 * 
	 * @param unknown $path
	 * @throws \Exception
	 * @return \GlobIterator
	 */
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
			//throw new \Exception("No json file found at [$path/set]");
		}
		
		
		$rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$path/$directory/"));

		$files = array(); 
		foreach ($rii as $file){
			if (!$file->isDir()){
				$files[] = $file;
			}
		}
		return $files;
		
		return $iterator;
	}
}
