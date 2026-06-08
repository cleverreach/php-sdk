<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Model\ReceiverModel;
use CleverReach\SDK\Exception\CleverReachException;
use CleverReach\SDK\Exception\ResourceNotFoundException;

final class ReceiversService extends AbstractService
{
    /**
     * Returns a single receiver by its numeric ID or email address.
     *
     * @param int|string $id      Numeric receiver ID or email address
     * @param null|int   $groupId Optional group ID to scope the lookup
     *
     * @throws CleverReachException
     */
    public function get(int|string $id, ?int $groupId = null): ReceiverModel {
        $encodedId = $this->encodeReceiverId($id);
        $response = $this->requestor->request('GET', "receivers/{$encodedId}", [
            'group_id' => $groupId,
        ]);

        if (array_is_list($response)) {
            if (empty($response)) {
                throw new ResourceNotFoundException(
                    "Receiver '{$id}' not found."
                );
            }
            $response = $response[0];
        }

        return ReceiverModel::fromArray(is_array($response) ? $response : []);
    }

    private function encodeReceiverId(int|string $id): string {
        return str_replace('%40', '@', rawurlencode((string) $id));
    }
}
