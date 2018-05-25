<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ImportGuidsCommand extends ContainerAwareCommand
{

	protected function configure()
	{
		$this
		->setName('app:import:guids')
		->setDescription('Download OCTGN IDs from CGDB')
		->addArgument(
		'setid',
		InputArgument::OPTIONAL,
		'GUID of the set for OCTGN'
		)
		;
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getContainer()->get('doctrine')->getManager();

		$setid = $input->getArgument('setid');

		/* @var $repo \AppBundle\Entity\ReviewRepository */
		$repo = $em->getRepository('AppBundle:Card');

		$setrepo = $em->getRepository('AppBundle:Pack');
		if ($setid){
			$sets = $setrepo->findBy(['code' => $setid], ['code' => 'ASC']);
		} else {
			$sets = $setrepo->findBy([], ['code' => 'ASC']);
		}
		

		foreach($sets as $set) {
			$setname = $set->getName();
			if ($setname == "Books"){
				$setname = "Book Series";
			}
			if ($setname == "The Dunwich Legacy"){
				$setname = "Dunwich Legacy";
			}
			$url = "https://raw.githubusercontent.com/GeckoTH/arkham-horror/master/o8g/Sets/".rawurlencode($setname)."/set.xml";
			
			// check url exists 
			$headers = @get_headers($url);
			if(strpos($headers[0],'200') === false) {
				$url = str_replace("The%20", "", $url);
				$headers = @get_headers($url);
				if(strpos($headers[0],'200') === false) {
					continue;
				}
			}
			$xmlstr = file_get_contents($url);
			echo $setname;
			echo "\n";
	
			$setxml = new \SimpleXMLElement($xmlstr);
			$cards = $setxml->cards[0];
			
			// parse the cards from the xml, into a guids array
			$guids = [];
			$guids_by_number = [];
			foreach($cards->children() as $card) {
				$attributes = $card->attributes();
				$name = (string)$attributes->name;
				$guid = (string)$attributes->id;
				$subtitle = "";
				$level = "";
				$back_name = "";
				$type = "";
				$number = "";
				foreach($card->children() as $key => $props) {
					$prop_atr = $props->attributes();
					if ((string)$prop_atr->name == "Subtitle"){
						$subtitle = (string) $prop_atr->value;
					}
					if ((string)$prop_atr->name == "Level" && intval($prop_atr->value) > 0){
						$level = (string) $prop_atr->value;
					}
					if ((string)$prop_atr->name == "Type"){
						$type = (string) $prop_atr->value;
					}
					if ((string)$prop_atr->name == "Card Number"){
						$number = (integer) $prop_atr->value;
					}
					if ($key == "alternate" && (string)$prop_atr->type == "B"){
						$back_name .= (string) $prop_atr->name;
					}
				}
				//if ($subtitle){
				//	$name .= $subtitle;
				//}
				//if ($back_name && $type != "Investigator" && $type != "Mini") {
				//	$name .= $back_name;
				//}
				//if ($level && $type != "Agenda" && $type != "Act") {
				//	$name .= $level;
				//}
				$name  = str_replace('’', '\'', $name);
				if (isset($guids[$name])) {
					$guids[$name] .= ":".$guid;
				} else {
					$guids[$name] = $guid;
				}
				if (isset($guids_by_number[$number])) {
					$guids_by_number[$number] .= ":".$guid;
				} else {
					$guids_by_number[$number] = $guid;
				}
			}

			$cards = $repo->findBy(['pack' => $set], ['code' => 'ASC']);
			
			foreach($cards as $card) {
				$name = $card->getName();
				$number = $card->getPosition();
				if ($card->getType()->getCode() == "investigator") {
					//if ($card->getSubname()){
					//	$name .= $card->getSubname();
					//}
					//if ($card->getBackname()){
					//	$name .= $card->getBackname();
					//}
					//if ($card->getXp()){
					//	$name .= $card->getXp();
					//}
					
					if(key_exists($name, $guids)) {
						$output->writeln("<info>Updating octgn id for $name (".$card->getName().") => ".$guids[$name]."</info>");
						$card->setOctgnId($guids[$name]);
						unset($guids[$name]);
						continue;
					}
				} else {
					// get from number guuids
					if(key_exists($number, $guids_by_number)) {
						$output->writeln("<info>Updating octgn id for ".$name." ".$number." (".$card->getName().")</info>");
						$card->setOctgnId($guids_by_number[$number]);
						unset($guids_by_number[$number]);
						continue;
					}
				}
				$output->writeln("<info>Missed OCGTN id for ".$card->getName()."</info>");
				
			}
			
			//print_r($guids_by_number);
			$em->flush();
			
		}
	}
}
