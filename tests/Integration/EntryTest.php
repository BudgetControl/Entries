<?php

namespace Budgetcontrol\Tests\Integration;

use MLAB\PHPITest\Service\HttpRequest;

class EntryTest extends BaseCase
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

    public function test_get_incoming_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->get("/1");
        $response->assertStatus(200);
        $response->assertJsonStructure(self::ENTRIES);
    }

    public function test_get_specific_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->get("/1/f7b3b3b0-0b7b-11ec-82a8-0242ac130003");
        $response->assertStatus(200);
        $response->assertJsonStructure(self::ENTRIES['data'][0]);
    }

    public function test_delete_data()
    {
        $request = new HttpRequest([], self::DOMAIN);

        $response = $request->delete("/1/f7b3b3b0-0b7b-11ec-82a8-delete");
        $response->assertStatus(204);
    }
}
