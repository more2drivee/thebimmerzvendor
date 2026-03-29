<?php

namespace App\Exceptions;

use Exception;

class PaymentAccountInsufficientBalance extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function render($request)
    {
        $output = [
            'success' => 0,
            'msg' => $this->getMessage(),
        ];

        if ($request->ajax()) {
            return $output;
        }

        throw new Exception($this->getMessage());
    }
}
