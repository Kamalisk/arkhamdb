<?php


namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class Diff
{
    public function __construct(EntityManager $doctrine)
    {
        $this->em = $doctrine;
    }
    
    public function diffContents($decks)
    {

        // n flat lists of the cards of each decklist
        $ensembles = [];
        foreach($decks as $deck) {
            $cards = [];
            foreach($deck as $code => $qty) {
                for($i=0; $i<$qty; $i++) $cards[] = $code;
            }
            $ensembles[] = $cards;
        }
        
        // 1 flat list of the cards seen in every decklist
        $conjunction = [];
        for($i=0; $i<count($ensembles[0]); $i++) {
            $code = $ensembles[0][$i];
            $indexes = array($i);
            for($j=1; $j<count($ensembles); $j++) {
                $index = array_search($code, $ensembles[$j]);
                if($index !== FALSE) $indexes[] = $index;
                else break;
            }
            if(count($indexes) === count($ensembles)) {
                $conjunction[] = $code;
                for($j=0; $j<count($indexes); $j++) {
                    $list = $ensembles[$j];
                    array_splice($list, $indexes[$j], 1);
                    $ensembles[$j] = $list;
                }
                $i--;
            }
        }
        
        $listings = [];
        for($i=0; $i<count($ensembles); $i++) {
            $listings[$i] = array_count_values($ensembles[$i]);
        }
        $intersect = array_count_values($conjunction);
        
        return array($listings, $intersect);
    }
}