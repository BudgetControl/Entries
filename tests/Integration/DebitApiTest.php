<?php

namespace Budgetcontrol\Tests\Integration;

use Budgetcontrol\Entry\Domain\Enum\EntryType;
use MLAB\PHPITest\Service\HttpRequest;

class DebitApiTest extends BaseCase
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
                "payee" => [
                    "id",
                    "uuid",
                    "name",
                    "date_time",
                    "workspace_id"
                ],
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

    public function test_get_debit_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->get("/1/debit");
        $response->assertStatus(200);
        $response->assertJsonStructure(self::ENTRIES);

        $isTrue = false;
        foreach($response->json()['data'] as $entry) {
            $isTrue = $entry->type === EntryType::debit->value;
        }
        $this->assertTrue($isTrue);
    }

    public function test_get_specific_debit_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->get("/1/debit/2b598724-4766-4bec-9529-da3196533d22");
        $response->assertStatus(200);
        $response->assertJsonStructure(self::ENTRIES['data'][0]);
    }

    public function test_create_debit_data()
    {
        $request = new HttpRequest([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], self::DOMAIN);
        $data = $this->makeRequest(-100);
        $data['payee_id'] = 'test';

        $response = $request->post("/1/debit", $data);
        $response->assertStatus(201);
    }

    public function test_update_debit_data()
    {
        $request = new HttpRequest([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], self::DOMAIN);
        $data = $this->makeRequest(-100);
        $data['payee_id'] = 'test';

        $response = $request->put("/1/debit/f7b3b3b0-0b7b-11ec-82a8-0242ac130003", $data);
        $response->assertStatus(200);
    }
}
