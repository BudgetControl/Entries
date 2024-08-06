<?php

namespace Budgetcontrol\Test\Integration;

use MLAB\PHPITest\Service\HttpRequest;
use Budgetcontrol\Library\Entity\Entry;
use Slim\Http\Interfaces\ResponseInterface;
use Budgetcontrol\Test\Integration\BaseCase;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\IncomingController;
use MLAB\PHPITest\Assertions\JsonAssert;
use MLAB\PHPITest\Entity\Json;

class IncomeApiTest extends BaseCase
{

    public function test_get_income_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new IncomingController();
        $result = $controller->get($request, $response, $argv);
        $contentResult = json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());

        $isTrue = false;
        foreach ($contentResult->data as $entry) {
            if ($entry->type === Entry::incoming->value) {
                $isTrue = true;
                break;
            }
        }

        $contentArray = json_decode(json_encode($contentResult));

        $this->assertTrue($isTrue);
        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/entry-model.json')
        );
    }

    public function test_get_specific_income_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130003'];

        $controller = new IncomingController();
        $result = $controller->show($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::incoming->value);
    }

    public function test_create_incoming_data()
    {
        $payload = $this->makeRequest(100);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new IncomingController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130003'];
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::incoming->value);
    }

    public function test_update_incoming_data()
    {
        $payload = $this->makeRequest(200);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        
        $response = $this->createMock(ResponseInterface::class);

        $controller = new IncomingController();
        $argv = ['wsid' => 1, 'uuid' => 'd373d245-512d-4bff-b414-9d59781be3ee'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::incoming->value);
        $this->assertTrue($contentResult['amount'] === 200);
    }

    public function test_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new IncomingController();
        $argv = ['wsid' => 1, 'uuid' => 'd373d245-512d-4bff-b414-9d59781be3ee'];
        $result = $controller->delete($request, $response, $argv);
        
        $this->assertEquals(204, $result->getStatusCode());
    }

    public function test_get_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new IncomingController();
        $argv = ['wsid' => 1, 'uuid' => 'd373d245-512d-4bff-b414-9d59781be3ee'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }
}
