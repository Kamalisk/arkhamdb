<?php


namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;

class Highlight
{

    public function __construct(EntityManager $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    public function save ()
    {
    
        $dbh = $this->doctrine->getConnection();
        $rows = $dbh->executeQuery(
                "SELECT
				d.id,
				d.ts,
				d.name,
				d.name_canonical,
				d.date_creation,
				d.description_md,
				d.description_html,
				d.precedent_decklist_id precedent,
				u.id user_id,
				u.username,
				u.color usercolor,
				u.reputation,
	            u.donation,
				f.code faction_code,
				d.nb_votes,
				d.nb_favorites,
				d.nb_comments
				from decklist d
				join user u on d.user_id=u.id
				join faction f on d.faction_id=f.id
				where d.date_creation > date_sub( current_date, interval 7 day )
                order by nbVotes desc , nbcomments desc
                limit 0,1
				", [])->fetchAll();
    
        if (empty($rows)) {
            return null;
        }
    
        $decklist = $rows[0];
    
        $cards = $dbh->executeQuery("SELECT
				c.code card_code,
				s.quantity qty
				from decklistslot s
				join card c on s.card_id=c.id
				where s.decklist_id=?
				order by c.code asc", array(
    				        $decklist['id']
    				))->fetchAll();
    
    				$decklist['cards'] = $cards;
    
    				$json = json_encode($decklist);
    				$dbh->executeQuery("INSERT INTO highlight (id, decklist) VALUES (?,?) ON DUPLICATE KEY UPDATE decklist=values(decklist)", array(
    				        1,
    				        $json
    				));
    
    				return $json;
    
    }
    
    public function get()
    {
        $dbh = $this->doctrine->getConnection();
        $decklist = null;
        $rows = $dbh->executeQuery("SELECT decklist from highlight where id=?", array(1))->fetchAll();

        if (empty($rows)) {
            $decklist = $this->save();
        } else {
            $decklist = $rows[0]['decklist'];
        }
        
        return json_decode($decklist);
    }
    
}