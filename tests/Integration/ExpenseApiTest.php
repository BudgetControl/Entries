<?php

namespace Budgetcontrol\Tests\Integration;

use Budgetcontrol\Entry\Domain\Enum\EntryType;
use MLAB\PHPITest\Service\HttpRequest;

class ExpenseApiTest extends BaseCase
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

    public function test_get_expenses_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->get("/1/expense");
        $response->assertStatus(200);
        $response->assertJsonStructure(self::ENTRIES);

        $isTrue = false;
        foreach($response->json()['data'] as $entry) {
            $isTrue = $entry->type === EntryType::expenses->value;
        }
        $this->assertTrue($isTrue);
    }

    public function test_get_specific_expenses_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->get("/1/2b598724-4766-4bec-9529-da3196533d11");
        $response->assertStatus(200);
        $response->assertJsonStructure(self::ENTRIES['data'][0]);
    }

    public function test_create_expenses_data()
    {
        $request = new HttpRequest([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], self::DOMAIN);
        $data = $this->makeRequest(-100);

        $response = $request->post("/1/expense", $data);
        $response->assertStatus(201);
    }

    public function test_update_expenses_data()
    {
        $request = new HttpRequest([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], self::DOMAIN);
        $data = $this->makeRequest(-100);

        $response = $request->put("/1/expense/f7d92908-bc1a-4336-8c2d-fb1648eacbe6", $data);
        $response->assertStatus(200);
    }
}
