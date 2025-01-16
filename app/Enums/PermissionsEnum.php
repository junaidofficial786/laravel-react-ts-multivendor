<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    case ApproveVendors = 'ApproveVendors';
    case SellVendors = 'SellVendors';
    case BuyProducts = 'BuyProducts';
}
