<?php

declare(strict_types=1);

namespace Tests\BitExpert\SyliusTwoFactorAuthPlugin\Entity\User;

use BitExpert\SyliusTwoFactorAuthPlugin\Entity\TwoFactorAuthInterface;
use BitExpert\SyliusTwoFactorAuthPlugin\Entity\TwoFactorAuthTrait;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ShopUser as BaseShopUser;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_shop_user')]
class ShopUser extends BaseShopUser implements TwoFactorAuthInterface
{
    use TwoFactorAuthTrait;
}
