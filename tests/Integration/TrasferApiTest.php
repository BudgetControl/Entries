<?php

namespace Budgetcontrol\Tests\Integration;

use Budgetcontrol\Entry\Domain\Enum\EntryType;
use MLAB\PHPITest\Service\HttpRequest;

class TrasferApiTest extends BaseCase
{

    const ENTRIES = [
        "current_page",
        "data" => [
            [
                "uuid",
                "amount",
                "note",
                "type",
                "waranty",
                "transfer",
                "confirmed",
                "planned",
                "category_id",
                "account_id",
                "transfer_id",
                "currency_id",
                "payment_type",
                "payee_id",
                "geolocation",
                "label",
                "sub_category" => [
                    "id",
                    "date_time",
                    "uuid",
                    "name",
                    "category_id",
                    "category" => [
                        "id",
                        "date_time",
                        "uuid",
                        "name",
                        "icon"
                    ]
                ],
                "wallet" => [
                    "uuid",
                    "name",
                    "color"
                ]
            ]
        ]
    ];

    public function test_get_transfer_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->get("/1/transfer");
        $response->assertStatus(200);
        $response->assertJsonStructure(self::ENTRIES);

        $isTrue = false;
        foreach ($response->json()['data'] as $entry) {
            $isTrue = $entry->type === EntryType::transfer->value;
        }
        $this->assertTrue($isTrue);
    }

    public function test_get_specific_transfer_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->get("/1/transfer/f7b3b3b0-0b7b-11ec-82a8-0242ac139903");
        $response->assertStatus(200);
        $response->assertJsonStructure(self::ENTRIES['data'][0]);
    }

    public function test_create_transfer_data()
    {
        $request = new HttpRequest([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], self::DOMAIN);
        $data = $this->makeRequest(-100);
        $data['transfer_id'] = 1;

        $response = $request->post("/1/transfer", $data);
        $response->assertStatus(201);
    }

    public function test_update_transfer_data()
    {
        $request = new HttpRequest([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], self::DOMAIN);
        $data = $this->makeRequest(-100);
        $data['transfer_id'] = 1;

        $response = $request->put("/1/transfer/f7b3b3b0-0b7b-11ec-82a8-0242ac139903", $data);
        $response->assertStatus(200);
    }

    public function test_delete_transfer_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->delete("/1/transfer/f7b3b3b0-0b7b-11ec-82a8-0242ac139903");
        $response->assertStatus(204);
    }
}
