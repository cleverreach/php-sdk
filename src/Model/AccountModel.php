<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class AccountModel extends AbstractModel
{
    public function __construct(
        public readonly string $id,
        public readonly string $name = '',
        public readonly string $firstname = '',
        public readonly string $lastname = '',
        public readonly string $email = '',
        public readonly string $salutation = '',
        public readonly string $street = '',
        public readonly string $company = '',
        public readonly string $zip = '',
        public readonly string $city = '',
        public readonly string $phone = '',
        public readonly string $country = '',
        public readonly string $loginDomain = '',
        public readonly string $whitelabelId = ''
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (string)($data['id'] ?? ''),
            (string)($data['name'] ?? ''),
            (string)($data['firstname'] ?? ''),
            (string)($data['lastname'] ?? ''),
            (string)($data['email'] ?? ''),
            (string)($data['salutation'] ?? ''),
            (string)($data['street'] ?? ''),
            (string)($data['company'] ?? ''),
            (string)($data['zip'] ?? ''),
            (string)($data['city'] ?? ''),
            (string)($data['phone'] ?? ''),
            (string)($data['country'] ?? ''),
            (string)($data['login_domain'] ?? ''),
            (string)($data['whitelabel_id'] ?? '')
        );
    }
}
