<?php

declare(strict_types=1);

namespace IWD\BillingSdk;

enum PurchaseStatus: string
{
    case Recorded = 'recorded';
    case Canceled = 'canceled';
}
