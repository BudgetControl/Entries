<?php
namespace Budgetcontrol\Entry\Controller;

use Budgetcontrol\Entry\Entity\Filter;
use Budgetcontrol\Entry\Entity\Order;
use Illuminate\Support\Carbon;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Library\Model\EntryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Library\Service\Wallet\WalletService;

class Controller {

    private readonly EntryInterface $oldEntry;

    public function monitor(Request $request, Response $response)
    {
        return response([
            'success' => true,
            'message' => 'Entries service is up and running'
        ]);
    }


    public function show(Request $request, Response $response, $argv): Response
    {
        $wsId = $argv['wsid'];
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
        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];
        $entries = Entry::where('workspace_id', $wsId)->where('uuid', $entryId)->first();

        if (empty($entries)) {
            return response([], 404);
        }

        $entries->delete();

        $wallet = new WalletService($entries);
        $wallet->subtract();

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

    protected function filters(\Illuminate\Database\Eloquent\Builder &$query, Filter $filters): \Illuminate\Database\Eloquent\Builder
    {
        foreach($filters->getFilters() as $key => $value) {
                if(isset($value['condition'])) {
                    $query->where($key, $value['condition'], $value['value']);
                }elseif(is_array($value['value'])) {
                    $query->whereIn($key, $value['value']);
                }else {
                    $query->where($key, $value['value']);
                }
        }

        return $query;
    }

    public function orderBy(\Illuminate\Database\Eloquent\Builder &$query, Order $orders): \Illuminate\Database\Eloquent\Builder
    {
        if($orders->getOrder()) {
            foreach($orders->getOrder() as $key => $order) {
                $query->orderBy($key, $order);
            }
        }

        return $query;
    }
}