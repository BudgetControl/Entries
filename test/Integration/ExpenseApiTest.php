<?php

namespace Budgetcontrol\Test\Integration;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Utils;
use MLAB\PHPITest\Entity\Json;
use MLAB\PHPITest\Service\HttpRequest;
use Budgetcontrol\Library\Entity\Entry;
use Psr\Http\Message\ResponseInterface;
use MLAB\PHPITest\Assertions\JsonAssert;
use Budgetcontrol\Test\Integration\BaseCase;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Wallet\Domain\Model\Wallet;
use Budgetcontrol\Entry\Controller\ExpensesController;
use Budgetcontrol\Wallet\Http\Controller\WalletController;

class ExpenseApiTest extends BaseCase
{

    public function test_get_expenses_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new ExpensesController();
        $result = $controller->get($request, $response, $argv);
        $contentArray = json_decode((string) $result->getBody());

        $isTrue = false;
        foreach($contentArray->data as $entry) {
            $isTrue = $entry->type === Entry::expenses->value;
        }
        $this->assertTrue($isTrue);

        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/entry-model.json')
        );
    }

    public function test_get_specific_expenses_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d11'];

        $controller = new ExpensesController();
        $result = $controller->show($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::expenses->value);
    }

    public function test_create_expenses_data()
    {
        $payload = $this->makeRequest(-100);
        $argv = ['wsid' => 1];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::expenses->value);

    }

    public function test_update_expenses_data()
    {
        $payload = $this->makeRequest(-100);
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d11'];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::expenses->value);

    }

    public function test_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d11'];
        $result = $controller->delete($request, $response, $argv);
        
        $this->assertEquals(204, $result->getStatusCode());
    }

    public function test_get_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d11'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }
}
