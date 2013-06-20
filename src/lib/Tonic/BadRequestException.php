<?php

namespace Tonic;

class BadRequestException extends Exception
{
    protected $code = 400;
    protected $message = 'Malformed or bad request';
}
