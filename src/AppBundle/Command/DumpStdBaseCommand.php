<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class DumpStdBaseCommand extends ContainerAwareCommand
{

	protected function configure()
	{
		$this
		->setName('app:dump:std:base')
		->setDescription('Dump Base data')
		->addArgument(
				'entityName',
				InputArgument::REQUIRED,
				"Entity (cycle, pack, faction, type)"
				)
				;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$entityName = $input->getArgument('entityName');
		$entityFullName = 'AppBundle:'.ucfirst($entityName);

		$repository = $this->getContainer()->get('doctrine')->getManager()->getRepository($entityFullName);

		$qb = $repository->createQueryBuilder('e')->orderBy('e.code');

		$result = $qb->getQuery()->getResult();

		$arr = [];

		foreach($result as $record) {
			$arr[] = $record->serialize();
		}

		$output->write(json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ));
		$output->writeln("");
	}
}