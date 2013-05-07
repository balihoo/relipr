<?php

namespace Tonic;

class ForbiddenException extends Exception
{
    protected $code = 403;
    protected $message = 'The server refuses to fulfill this request';
}
