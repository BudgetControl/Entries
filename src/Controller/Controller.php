<?php
namespace Budgetcontrol\Entry\Controller;

use Budgetcontrol\Entry\Entity\Filter;
use Budgetcontrol\Entry\Entity\Order;
use Budgetcontrol\Library\Entity\Entry as EntityEntry;
use Illuminate\Support\Carbon;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Library\Model\EntryInterface;
use Budgetcontrol\Library\Model\Label;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Library\Service\Wallet\WalletService;

class Controller {

    private readonly EntryInterface $oldEntry;
    protected int $workspaceId;

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

    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder &$query The query builder instance.
     * @param Filter $filters The filter instance.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder instance.
     */
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

    /**
     * Orders the query results based on the given orders.
     *
     * @param \Illuminate\Database\Eloquent\Builder &$query The query builder instance.
     * @param Order $orders The orders to apply to the query.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder instance.
     */
    public function orderBy(\Illuminate\Database\Eloquent\Builder &$query, Order $orders): \Illuminate\Database\Eloquent\Builder
    {
        if($orders->getOrder()) {
            foreach($orders->getOrder() as $key => $order) {
                $query->orderBy($key, $order);
            }
        }

        return $query;
    }

    /**
     * Creates or gets a label.
     *
     * @param string|int $name The name of the label.
     * @param string|null $color The color of the label.
     * @return Label The created or retrieved label.
     */
    public function createOrGetLabel(string|int $name, ?string $color): Label
    {
        if(!isset($this->workspaceId)) {
            throw new \Exception('Workspace ID is not set');
        }
        
        // first check if label exists
        if(is_int($name)) {
            return Label::find($name);
        }

        // check if label exists
        $label = Label::where('name', $name)->where('workspace_id', $this->workspaceId)->first();
        if($label) {
            return $label;
        }

        // if label does not exist, create it
        $label = new Label();
        $label->name = $name;
        $label->uuid = \Ramsey\Uuid\Uuid::uuid4();
        $label->color = $color ?? '#000000';
        $label->workspace_id = $this->workspaceId;
        $label->save();

        return $label;

    }

    /**
     * Retrieves the category ID associated with a given entry type.
     *
     * @param string $type The type of the entry.
     * @param int $currentValue The current value associated with the entry type.
     * @return int The category ID corresponding to the entry type.
     */
    public function retriveCategoryIdOfEntryType(string $type, int $currentValue): int
    {
        switch($type) {
            case EntityEntry::debit->value:
                return 55;
            case EntityEntry::transfer->value:
                return 75;
            default:
                return $currentValue;
        }
    }
}