<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Collection\ModelCollection;
use CleverReach\SDK\Exception\CleverReachException;
use CleverReach\SDK\Exception\ResourceNotFoundException;
use CleverReach\SDK\Model\BlacklistRequest;
use CleverReach\SDK\Model\BlacklistValidationModel;
use CleverReach\SDK\Model\ReceiverModel;
use CleverReach\SDK\Model\ReceiverRequest;

final class ReceiversService extends AbstractService
{
    /**
     * @param int|string $id Numeric receiver ID or email address
     * @param null|int $groupId Optional group ID to scope the lookup
     * @throws CleverReachException
     */
    public function get(int|string $id, ?int $groupId = null): ReceiverModel {
        $encodedId = $this->encodeReceiverId($id);
        $response = $this->requestor->request('GET', "receivers/{$encodedId}", [
            'group_id' => $groupId,
        ]);

        if (array_is_list($response)) {
            if (empty($response)) {
                throw new ResourceNotFoundException("Receiver '{$id}' not found.");
            }
            $response = $response[0];
        }

        return ReceiverModel::fromArray($response);
    }

    /**
     * @param list<mixed> $receivers
     * @return ModelCollection<ReceiverModel>
     */
    public function upsertPlus(int $groupId, array $receivers): ModelCollection {
        foreach ($receivers as $receiver) {
            if (!$receiver instanceof ReceiverRequest) {
                throw new \InvalidArgumentException('Receivers must be ReceiverRequest instances.');
            }
        }
        $payload = array_map(
            static fn (ReceiverRequest $receiver): array => $receiver->toArray(),
            $receivers
        );
        $response = $this->requestor->request('POST', "groups/{$groupId}/receivers/upsertplus", [], $payload);
        if (!array_is_list($response)) {
            $response = [$response];
        }

        return $this->collection($response, ReceiverModel::class);
    }

    public function delete(string $email): void {
        $this->requestor->request('DELETE', 'receivers/'.$this->encodeReceiverId($email));
    }

    /** @param list<string> $emails */
    public function deleteBatch(array $emails): void {
        $this->requestor->request('POST', 'receivers/delete', [], ['receivers' => $emails]);
    }

    public function blacklist(BlacklistRequest $blacklist): void {
        $this->requestor->request('POST', 'blacklist', [], $blacklist->toArray());
    }

    public function whitelist(string $email): void {
        $this->requestor->request('DELETE', 'blacklist/'.$this->encodeReceiverId($email));
    }

    /** @param list<string> $emails */
    public function validateBlacklist(int $groupId, array $emails): BlacklistValidationModel {
        $response = $this->requestor->request('POST', 'blacklist/validate', [], [
            'emails' => $emails,
            'group_id' => $groupId,
            'invert' => true,
        ]);

        return BlacklistValidationModel::fromArray($this->firstRow($response));
    }

    public function filterByEmail(int $groupId, string $email): ?ReceiverModel {
        $response = $this->requestor->request('POST', 'receivers/filter', [], [
            'groups' => [$groupId],
            'rules' => [
                ['field' => 'email', 'logic' => 'eq', 'condition' => $email],
            ],
        ]);

        return isset($response[0]) ? ReceiverModel::fromArray($response[0]) : null;
    }

    public function getFromGroup(int $groupId, int|string $receiverId): ReceiverModel {
        $encodedId = $this->encodeReceiverId($receiverId);
        $response = $this->requestor->request('GET', "groups/{$groupId}/receivers/{$encodedId}");

        if (array_is_list($response)) {
            if (empty($response)) {
                throw new ResourceNotFoundException("Receiver '{$receiverId}' not found in group '{$groupId}'.");
            }
            $response = $response[0];
        }

        return ReceiverModel::fromArray($response);
    }

    private function encodeReceiverId(int|string $id): string {
        return str_replace('%40', '@', rawurlencode((string)$id));
    }
}
