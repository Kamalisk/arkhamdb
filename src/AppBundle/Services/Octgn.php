<?php


namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use Twig_Environment;

class Octgn
{
	public function __construct(EntityManager $doctrine, Twig_Environment $twig)
	{
    $this->doctrine = $doctrine;
    $this->twig = $twig;
	}

    /**
	 * @param $deck Deck or Decklist
     */
    public function export($deck)
    {
      $types = [
        'Faction' => 'faction',
        'Agenda' => 'agenda',
        'Plots' => 'plot',
        'Characters' => 'character',
        'Attachments' => 'attachment',
        'Events' => 'event',
        'Locations' => 'location'
      ];

      $xml = [];

      foreach($types as $label => $type_code) {
        $xml[] = sprintf('  <section name="%s" shared="False">', $label);

        if($type_code === 'faction') {
          $xml[] = sprintf('    <card qty="1" id="%s">%s</card>', $deck->getFaction()->getOctgnid(), $deck->getFaction()->getName());
        }
        else {
          foreach($deck->getSlots() as $slot) {
            if($slot->getCard()->getType()->getCode() === $type_code) {
              $xml[] = sprintf('    <card qty="%d" id="%s">%s</card>', $slot->getQuantity(), $slot->getCard()->getOctgnid(), $slot->getCard()->getName());
            }
          }
        }

        $xml[] = '  </section>';
      }

      return $this->twig->render('AppBundle::octgn.xml.twig', [
        "content" => implode("\n", $xml)
      ]);

    }
}
