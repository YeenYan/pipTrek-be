<?php

namespace Src\Modules\Authentication\GraphQL\Scalars;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\StringValueNode;

class JSON extends ScalarType
{
    public string $name = 'JSON';
    public ?string $description = 'Arbitrary JSON data as a native object.';

    public function serialize($value): mixed
    {
        return $value;
    }

    public function parseValue($value): mixed
    {
        return $value;
    }

    public function parseLiteral($valueNode, ?array $variables = null): mixed
    {
        if ($valueNode instanceof StringValueNode) {
            return json_decode($valueNode->value, true);
        }

        return null;
    }
}