<?php

declare(strict_types=1);

namespace IWD\BillingSdk;

enum PurchaseItem: string
{
    case Tariff = 'tariff';
    case Service = 'service';
}
