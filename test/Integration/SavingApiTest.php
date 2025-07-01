<?php

namespace Budgetcontrol\Test\Integration;

use MLAB\PHPITest\Entity\Json;
use Psr\Http\Message\ResponseInterface;
use MLAB\PHPITest\Assertions\JsonAssert;
use Budgetcontrol\Test\BaseCase;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\SavingController;
use Budgetcontrol\Library\Model\Wallet;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Model\Entry as EntryModel;
use Budgetcontrol\Library\Model\Goal;

class SavingApiTest extends BaseCase
{

    public function test_get_saving_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1, 'goalUuid' => '148defec-ae28-4779-bf4e-b6e999c93f26'];

        $controller = new SavingController();
        $result = $controller->get($request, $response, $argv);
        $contentArray = json_decode((string) $result->getBody());

        $isTrue = false;
        foreach($contentArray->data as $entry) {
            $isTrue = $entry->type === Entry::saving->value;
        }
        $this->assertTrue($isTrue);
        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/entry-savings.json')
        );

    }

    public function test_get_specific_saving_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1, 'uuid' => '1b7674a9-49ab-418c-a84c-c24ae48ecbbc'];

        $controller = new SavingController();
        $result = $controller->show($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::saving->value);
    }

    public function test_create_saving_data()
    {
        $payload = $this->makeRequest(100);
        $argv = ['wsid' => 1];
        $payload['goal_id'] = 1; // Assuming goal_id is required for saving entries

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new SavingController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::saving->value);
        $this->assertNotEmpty(EntryModel::where('uuid', $contentResult['uuid'])->first());

        $wallet = Wallet::find(1);
        $this->assertEquals(-100, $wallet->balance);

        $goal = Goal::find(1);
        $this->assertEquals(100, $goal->balance);
        
    }

    public function test_update_SavingController_data()
    {
        $payload = $this->makeRequest(150);
        $argv = ['wsid' => 1, 'uuid' => '1b7674a9-49ab-418c-a84c-c24ae48ecbbc'];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new SavingController();
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::saving->value);
        $this->assertTrue($contentResult['amount'] === 150);

        $wallet = Wallet::find(1);
        $this->assertEquals(250, $wallet->balance);

        $goal = Goal::find(1);
        $this->assertEquals(-250, $goal->balance);

    }

    public function test_create_saving_data_with_labels()
    {
        $payload = $this->makeRequest(100);
        $payload['confirm'] = false;
        $payload['labels'] = [
            [
                'name' => 1,
                'color' => null
            ],
            [
                'name' => 2,
                'color' => null
            ],
         ];
        $argv = ['wsid' => 1];
        $payload['goal_id'] = 1; // Assuming goal_id is required for saving entries

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new SavingController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::saving->value);
        $this->assertNotEmpty(EntryModel::where('uuid', $contentResult['uuid'])->first());

        $wallet = Wallet::find(1);
        $enstry = EntryModel::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertEquals(-100, $wallet->balance);
        $this->assertCount(2, $enstry->labels);

        $goal = Goal::find(1);
        $this->assertEquals(100, $goal->balance);
        
    }

    public function test_create_saving_data_with_new_labels()
    {
        $payload = $this->makeRequest(100);
        $payload['confirm'] = false;
        $payload['labels'] = [
            [
                'name' => 'new-label',
                'color' => '#000'
            ],
         ];
        $argv = ['wsid' => 1];
        $payload['goal_id'] = 1; // Assuming goal_id is required for saving entries

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new SavingController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::saving->value);
        $this->assertNotEmpty(EntryModel::where('uuid', $contentResult['uuid'])->first());

        $wallet = Wallet::find(1);
        $enstry = EntryModel::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertEquals(-100, $wallet->balance);
        $this->assertCount(1, $enstry->labels);

        $goal = Goal::find(1);
        $this->assertEquals(100, $goal->balance);
        
    }

    public function test_update_saving_data_with_new_label()
    {
        $payload = $this->makeRequest(300);
        $payload['labels'] = [
            [
                'name' => 'new-label',
                'color' => '#000'
            ],
            [
                'name' => 1,
                'color' => null
            ],
            [
                'name' => 2,
                'color' => null
            ],
         ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        
        $response = $this->createMock(ResponseInterface::class);

        $controller = new SavingController();
        $argv = ['wsid' => 1, 'uuid' => '1b7674a9-49ab-418c-a84c-c24ae48ecbbc'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::saving->value);
        $this->assertTrue($contentResult['amount'] === 300);

        $enstry = EntryModel::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(3, $enstry->labels);
    }


    public function test_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new SavingController();
        $argv = ['wsid' => 1, 'uuid' => '1b7674a9-49ab-418c-a84c-c24ae48ecbbc'];
        $result = $controller->delete($request, $response, $argv);
        
        $this->assertEquals(204, $result->getStatusCode());
    }

    public function test_get_deleted_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new SavingController();
        $argv = ['wsid' => 1, 'uuid' => '1b7674a9-49ab-418c-a84c-c24ae48ecbbc'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }
}
