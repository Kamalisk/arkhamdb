<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateClientCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:oauth-server:client:create')
            ->setDescription('Creates a new client')
            ->addOption(
                'grant-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets allowed grant type for client. Use this option multiple times to set multiple grant types..',
                ["authorization_code", "refresh_token"]
            )
            ->addArgument(
                'redirect-uri',
            	InputArgument::REQUIRED,
                'Sets redirect uri for client'
            )
            ->addArgument(
                'client-name',
                InputArgument::REQUIRED,
                'Sets the displayed name of the client'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	$redirectUris = [ $input->getArgument('redirect-uri') ];
    	
        $clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();
        $client->setRedirectUris($redirectUris);
        $client->setAllowedGrantTypes($input->getOption('grant-type'));
        $client->setName($input->getArgument('client-name'));
        $clientManager->updateClient($client);
        $output->writeln(
            sprintf(
                'Added a new client with public id <info>%s</info>, secret <info>%s</info>',
                $client->getPublicId(),
                $client->getSecret()
            )
        );
    }
}
