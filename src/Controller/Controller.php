<?php
namespace Budgetcontrol\Entry\Controller;

use Illuminate\Support\Carbon;
use Budgetcontrol\Entry\Domain\Model\Entry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Controller {

    public function monitor(Request $request, Response $response)
    {
        return response([
            'success' => true,
            'message' => 'Entries service is up and running'
        ]);
    }


    public function show(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entryId = $argv['uuid'];
        $entries = Entry::WithRelations()->where('workspace_id', $wsId)->where('uuid', $entryId)
        ->where('deleted_at', null)
        ->first();

        if (!$entries) {
            return response([], 404);
        }

        return response(
            $entries->toArray()
        );
    }


    public function delete(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsis'];
        $entryId = $argv['uuid'];
        $entries = Entry::where('workspace_id', $wsId)->where('id', $entryId)->get();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        $entries->first()->delete();

        return response([], 204);
    }

    /**
     * Checks if a given date and time is planned.
     *
     * @param mixed $date_time The date and time to check.
     * @return bool Returns true if the date and time is planned, false otherwise.
     */
    protected function isPlanned($date_time): bool
    {
        //use carbon
        $date = Carbon::parse($date_time);
        $now = Carbon::now();

        return $date->gt($now);
    }
}