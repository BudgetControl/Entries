<?php

namespace Budgetcontrol\Test\Integration;

use MLAB\PHPITest\Entity\Json;
use Budgetcontrol\Test\BaseCase;
use Illuminate\Support\Facades\DB;
use Budgetcontrol\Entry\Facade\Crypt;
use Psr\Http\Message\ResponseInterface;
use MLAB\PHPITest\Assertions\JsonAssert;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\EntryController;
use Budgetcontrol\Entry\Controller\ExpensesController;
use Budgetcontrol\Entry\Controller\IncomingController;

class EntryTest extends BaseCase
{
    public function test_get_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new EntryController();
        $result = $controller->get($request, $response, $argv);

        $contentArray = json_decode((string) $result->getBody());
        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/entry-model.json')
        );
    }

    public function test_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new EntryController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-delete'];
        $result = $controller->delete($request, $response, $argv);
        
        $this->assertEquals(204, $result->getStatusCode());
    }

    public function test_get_deleted_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new EntryController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-delete'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function test_encrypt_decrypt_note()
    {
        $payload = $this->makeRequest(100);
        $payload['note'] = 'This is a test note';

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new IncomingController();
        $argv = ['wsid' => 1];
        $result = $controller->create($request, $response, $argv);
        $this->assertEquals(201, $result->getStatusCode());
        $uuid = json_decode((string) $result->getBody())->uuid;

        $noteToTest = Crypt::encrypt($payload['note']);
        $dbRaw = "select note from entries where uuid = '" . $uuid . "'";
        $contentToTest = DB::select($dbRaw);
        $contentResult = (array) $contentToTest[0];
        $this->assertEquals($noteToTest, $contentResult['note']);

        //check if the note can be decrypted
        $decryptedNote = Crypt::decrypt($contentResult['note']);
        $this->assertEquals($payload['note'], $decryptedNote);

    }

    public function test_create_entry_data_with_no_note_data()
    {
        $payload = $this->makeRequest(-100);
        $payload['note'] = null; // No note data
        $argv = ['wsid' => 1];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $result = $controller->create($request, $response, $argv);

        $this->assertEquals(201, $result->getStatusCode());
        
    }
}
