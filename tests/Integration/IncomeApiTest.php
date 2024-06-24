<?php

namespace Budgetcontrol\Tests\Integration;

use MLAB\PHPITest\Service\HttpRequest;
use Budgetcontrol\Entry\Domain\Enum\EntryType;

class IncomeApiTest extends BaseCase
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

    public function test_get_income_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->get("/1/income");
        $response->assertStatus(200);
        $response->assertJsonStructure(self::ENTRIES);

        $isTrue = false;
        foreach($response->json()['data'] as $entry) {
            $isTrue = $entry->type === EntryType::incoming->value;
        }
        $this->assertTrue($isTrue);
    }

    public function test_create_incoming_data()
    {
        $request = new HttpRequest([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], self::DOMAIN);
        $data = $this->makeRequest(100);

        $response = $request->post("/1/income", $data);
        $response->assertStatus(201);
    }

    public function test_update_incoming_data()
    {
        $request = new HttpRequest([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], self::DOMAIN);
        $data = $this->makeRequest(100);

        $response = $request->put("/1/income/d373d245-512d-4bff-b414-9d59781be3ee", $data);
        $response->assertStatus(200);
    }
}
