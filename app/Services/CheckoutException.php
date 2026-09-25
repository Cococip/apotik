<?php

namespace App\Services;

/**
 * Thrown for user-facing checkout failures (insufficient stock, invalid
 * medicine, etc.) — always caught and shown as a friendly message, never
 * leaked as a raw exception (§44).
 */
class CheckoutException extends \RuntimeException
{
}
