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

    /**
     * @param list<array<string, mixed>> $receivers
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function upsertPlus(int $groupId, array $receivers): array {
        return $this->requestor->request('POST', "groups.json/{$groupId}/receivers/upsertplus", [], $receivers);
    }

    public function delete(string $email): void {
        $this->requestor->request('DELETE', 'receivers.json/'.$this->encodeReceiverId($email));
    }

    /**
     * @param list<string> $emails
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function deleteBatch(array $emails): array {
        return $this->requestor->request('POST', 'receivers/delete.json', [], ['receivers' => $emails]);
    }

    /**
     * @param array<string, mixed> $blacklist
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function blacklist(array $blacklist): array {
        return $this->requestor->request('POST', 'blacklist.json/', [], $blacklist);
    }

    public function whitelist(string $email): void {
        $this->requestor->request('DELETE', 'blacklist.json/'.$this->encodeReceiverId($email));
    }

    /**
     * @param list<string> $emails
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function validateBlacklist(int $groupId, array $emails): array {
        return $this->requestor->request('POST', 'blacklist/validate.json', [], [
            'emails' => $emails,
            'group_id' => $groupId,
            'invert' => true,
        ]);
    }

    public function filterByEmail(int $groupId, string $email): ?ReceiverModel {
        $response = $this->requestor->request('POST', 'receivers/filter.json', [], [
            'groups' => [$groupId],
            'rules' => [
                ['field' => 'email', 'logic' => 'eq', 'condition' => $email],
            ],
        ]);

        return isset($response[0]) ? ReceiverModel::fromArray($response[0]) : null;
    }

    public function getFromGroup(int $groupId, int|string $receiverId): ReceiverModel {
        $response = $this->requestor->request('GET', "groups.json/{$groupId}/receivers/{$receiverId}");

        if (array_is_list($response)) {
            $response = $response[0] ?? [];
        }

        return ReceiverModel::fromArray($response);
    }

    private function encodeReceiverId(int|string $id): string {
        return str_replace('%40', '@', rawurlencode((string) $id));
    }
}
