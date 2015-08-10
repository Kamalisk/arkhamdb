<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class HighlightCommand extends ContainerAwareCommand
{

    protected function saveHighlight($decklist_id)
    {
        $dbh = $this->getContainer()->get('doctrine')->getConnection();

        if ($decklist_id)
        {
            $rows = $dbh
        ->executeQuery(
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
				where d.id=?
				", array($decklist_id))->fetchAll();

        } else {

            $rows = $dbh
            ->executeQuery(
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
    				c.code identity_code,
    				f.code faction_code,
    				d.nb_votes,
    				d.nb_favorites,
    				d.nb_comments
    				from decklist d
    				join user u on d.user_id=u.id
    				join card c on d.identity_id=c.id
    				join faction f on d.faction_id=f.id
    				where d.date_creation > date_sub( current_date, interval 7 day )
                    order by nbVotes desc , nbcomments desc
                    limit 0,1
    				", array())->fetchAll();
        }

        if(empty($rows)) {
            return false;
        }

        $decklist = $rows[0];

        $cards = $dbh
        ->executeQuery(
                "SELECT
				c.code card_code,
				s.quantity qty
				from decklistslot s
				join card c on s.card_id=c.id
				where s.decklist_id=?
				order by c.code asc", array($decklist['id']))->fetchAll();

        $decklist['cards'] = $cards;

        $json = json_encode($decklist);
        $dbh->executeQuery("INSERT INTO highlight (id, decklist) VALUES (?,?) ON DUPLICATE KEY UPDATE decklist=values(decklist)", array(1, $json));

        return true;
    }

    protected function configure()
    {
        $this
        ->setName('app:highlight')
        ->setDescription('Save decklist of the week')
        ->addArgument(
            'decklist_id',
            InputArgument::OPTIONAL,
            'Id for Decklist'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $decklist_id = $input->getArgument('decklist_id');
        $result = $this->saveHighlight($decklist_id);
        $output->writeln(date('c') . " " . ($result ? "Success" : "Failure"));
    }
}
