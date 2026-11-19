<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class ReceiverRequest implements RequestModelInterface
{
    /** @var list<string> */
    private array $provided = [];

    /**
     * @param null|array<string, mixed> $attributes
     * @param null|array<string, mixed> $globalAttributes
     * @param null|list<string> $tags
     * @param null|list<array<string, mixed>> $modifiers
     * @param null|list<array<string, mixed>> $orders
     */
    public function __construct(
        public readonly string $email,
        public readonly string $source = '',
        public readonly ?int $activated = null,
        public readonly ?int $deactivated = null,
        public readonly ?int $registered = null,
        public readonly ?array $attributes = null,
        public readonly ?array $globalAttributes = null,
        public readonly ?array $tags = null,
        public readonly ?array $modifiers = null,
        public readonly ?array $orders = null
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self {
        $request = new self(
            (string)($data['email'] ?? ''),
            (string)($data['source'] ?? ''),
            isset($data['activated']) ? (int)$data['activated'] : null,
            isset($data['deactivated']) ? (int)$data['deactivated'] : null,
            isset($data['registered']) ? (int)$data['registered'] : null,
            isset($data['attributes']) && is_array($data['attributes']) ? $data['attributes'] : null,
            isset($data['global_attributes']) && is_array($data['global_attributes']) ? $data['global_attributes'] : null,
            isset($data['tags']) && is_array($data['tags']) ? array_values($data['tags']) : null,
            isset($data['modifiers']) && is_array($data['modifiers']) ? array_values($data['modifiers']) : null,
            isset($data['orders']) && is_array($data['orders']) ? array_values($data['orders']) : null
        );
        $request->provided = array_keys($data);

        return $request;
    }

    public function toArray(): array {
        $result = ['email' => $this->email];
        $optional = [
            'source' => $this->source,
            'activated' => $this->activated,
            'deactivated' => $this->deactivated,
            'registered' => $this->registered,
            'attributes' => $this->attributes,
            'global_attributes' => $this->globalAttributes,
            'tags' => $this->tags,
            'modifiers' => $this->modifiers,
            'orders' => $this->orders,
        ];
        foreach ($optional as $key => $value) {
            if (($value !== null && ($key !== 'source' || $value !== '')) || in_array($key, $this->provided, true)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
