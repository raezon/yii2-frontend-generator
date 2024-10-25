<?php

namespace app\modules\ui\generator\config;

class ModelSchema
{
    public static function getSchema($model)
    {
        $schemas = [
            'product' => [
                'name' => 'string',
                'price' => 'float',
                'description' => 'text',
                'stock' => 'integer',
            ],
            'user' => [
                'username' => 'string',
                'email' => 'string',
                'password' => 'string',
            ],
            'cart' => [
                'user_id' => 'integer',
                'product_id' => 'integer',
                'quantity' => 'integer',
            ],
        ];

        return $schemas[$model] ?? [];
    }
}