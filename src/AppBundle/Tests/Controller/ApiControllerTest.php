<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    public function testGetCard()
    {
    	$client = static::createClient();
    	$client->request('GET', '/api/card/01001');
    	$response = $client->getResponse();
    	$json = $response->getContent();
    	$this->assertJson($json);
    	$data = json_decode($json, true);
    	$this->assertNotNull($data);
    	$this->assertInternalType('array', $data);
    	$this->assertNotEmpty($data);
    	$this->assertArrayHasKey("code", $data);
    	$this->assertEquals("01001", $data['code']);
    }

    public function testListCards()
    {
    	$client = static::createClient();
    	$client->request('GET', '/api/cards/');
    	$response = $client->getResponse();
    	$json = $response->getContent();
    	$this->assertJson($json);
    	$data = json_decode($json, true);
    	$this->assertNotNull($data);
    	$this->assertInternalType('array', $data);
    	$this->assertNotEmpty($data);
    }
    
    public function testListCardsByPack()
    {
    	$client = static::createClient();
    	$client->request('GET', '/api/cards/Core');
    	$response = $client->getResponse();
    	$json = $response->getContent();
    	$this->assertJson($json);
    	$data = json_decode($json, true);
    	$this->assertNotNull($data);
    	$this->assertInternalType('array', $data);
    	$this->assertNotEmpty($data);
    	foreach($data as $item)
    	{
    		$this->assertInternalType('array', $item);
    		$this->assertArrayHasKey("pack_code", $item);
    		$this->assertEquals("Core", $item['pack_code']);
    	}
    }
    
    public function testListPacks()
    {
    	$client = static::createClient();
       	$client->request('GET', '/api/packs/');
    	$response = $client->getResponse();
    	$json = $response->getContent();
    	$this->assertJson($json);
    	$data = json_decode($json, true);
    	$this->assertNotNull($data);
    	$this->assertInternalType('array', $data);
    	$this->assertNotEmpty($data);
    }

   	public function testGetDecklist()
   	{
    	$client = static::createClient();
       	$client->request('GET', '/api/decklist/1');
    	$response = $client->getResponse();
    	$json = $response->getContent();
    	$this->assertJson($json);
    	$data = json_decode($json, true);
    	$this->assertNotNull($data);
    	$this->assertInternalType('array', $data);
    	$this->assertArrayHasKey("id", $data);
    	$this->assertEquals(1, $data['id']);
    }

    public function testListDecklists()
    {
    	$client = static::createClient();
    	$client->request('GET', '/api/decklists/2015-08-16');
    	$response = $client->getResponse();
    	$json = $response->getContent();
    	$this->assertJson($json);
    	$data = json_decode($json, true);
    	$this->assertNotNull($data);
    	$this->assertInternalType('array', $data);
    	$this->assertNotEmpty($data);
    	foreach($data as $item)
    	{
    		$this->assertInternalType('array', $item);
    		$this->assertArrayHasKey("date_creation", $item);
    		$this->assertStringStartsWith('2015-08-16', $item['date_creation']);
    	}
    }
}
