<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Collection\GroupCollection;
use CleverReach\SDK\Collection\ReceiverCollection;
use CleverReach\SDK\Model\GroupModel;
use CleverReach\SDK\Enum\GroupSortField;
use CleverReach\SDK\Enum\ReceiverType;
use CleverReach\SDK\Enum\SortOrder;
use CleverReach\SDK\Exception\CleverReachException;
use CleverReach\SDK\Exception\ResourceNotFoundException;

final class GroupsService extends AbstractService
{
    /**
     * Returns a single group by its ID.
     *
     * @param int $id Numeric group ID
     *
     * @throws CleverReachException
     */
    public function get(int $id): GroupModel {
        $response = $this->requestor->request('GET', "groups/{$id}");

        if (array_is_list($response)) {
            if (empty($response)) {
                throw new ResourceNotFoundException(
                    "Group '{$id}' not found."
                );
            }
            $response = $response[0];
        }

        return GroupModel::fromArray(is_array($response) ? $response : []);
    }

    /**
     * Returns all groups, optionally sorted by field and direction.
     *
     * @param null|GroupSortField $order     Column to sort by
     * @param null|SortOrder      $direction Sort direction (ASC or DESC)
     *
     * @throws \CleverReach\SDK\Exception\AuthenticationException
     * @throws \CleverReach\SDK\Exception\RateLimitExceededException
     * @throws \CleverReach\SDK\Exception\CleverReachException
     */
    public function all(
        ?GroupSortField $order = null,
        ?SortOrder $direction = null
    ): GroupCollection {
        $response = $this->requestor->request('GET', 'groups', [
            'order' => $order ? trim("{$order->value} {$direction?->value}") : null,
        ]);

        if (!array_is_list($response)) {
            return GroupCollection::fromArrayList([]);
        }

        return GroupCollection::fromArrayList($response);
    }

    /**
     * Returns the receivers of a group with pagination and optional filters.
     *
     * @param int               $groupId        Numeric group ID
     * @param int               $page           Page number (starting at 0)
     * @param int               $pageSize       Number of results per page (max. 5000, default: 50)
     * @param null|ReceiverType $type           Filter by receiver status (default: all)
     * @param int               $detail         Detail depth, bitwise combinable: 0=none, 1=events, 2=orders, 4=tags
     * @param string[]          $emailList      List of email addresses to filter for
     * @param string[]          $idList         List of receiver IDs to filter for
     * @param null|string       $orderBy        Column to sort by
     * @param null|SortOrder    $orderDirection Sort direction (ASC or DESC)
     *
     * @throws CleverReachException
     */
    public function getReceivers(
        int $groupId,
        int $page = 0,
        int $pageSize = 50,
        ?ReceiverType $type = null,
        int $detail = 0,
        array $emailList = [],
        array $idList = [],
        ?string $orderBy = null,
        ?SortOrder $orderDirection = null,
    ): ReceiverCollection {
        $response = $this->requestor->request('GET', "groups/{$groupId}/receivers", [
            'page' => $page,
            'pagesize' => $pageSize,
            'type' => $type?->value,
            'detail' => $detail !== 0 ? $detail : null,
            'email_list' => $emailList !== [] ? implode(',', $emailList) : null,
            'id_list' => $idList !== [] ? implode(',', $idList) : null,
            'order_by' => $orderBy ? trim("{$orderBy} {$orderDirection?->value}") : null,
        ]);

        if (!array_is_list($response)) {
            return ReceiverCollection::fromArrayList([]);
        }

        return ReceiverCollection::fromArrayList($response);
    }
}
