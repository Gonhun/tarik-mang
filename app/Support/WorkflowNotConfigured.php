<?php

namespace App\Support;

use RuntimeException;

/**
 * Workflow approval belum diatur / approver tidak ditemukan. Pesannya aman ditampilkan ke user.
 */
class WorkflowNotConfigured extends RuntimeException
{
}
