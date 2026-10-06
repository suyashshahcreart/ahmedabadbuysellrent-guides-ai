<?php

namespace App\Services\Ai;

use RuntimeException;
use Throwable;

class GenerationException extends RuntimeException
{
    /**
     * @param  array{input_tokens?:int,output_tokens?:int,model?:string,raw_text?:string,prompt_summary?:string}  $context
     */
    public function __construct(string $message, public array $context = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
