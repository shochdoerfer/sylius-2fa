<?php

declare(strict_types=1);

namespace Tests\BitExpert\SyliusTwoFactorAuthPlugin\Entity\User;

use BitExpert\SyliusTwoFactorAuthPlugin\Entity\TwoFactorAuthInterface;
use BitExpert\SyliusTwoFactorAuthPlugin\Entity\TwoFactorAuthTrait;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\AdminUser as BaseAdminUser;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_admin_user')]
class AdminUser extends BaseAdminUser implements TwoFactorAuthInterface
{
    use TwoFactorAuthTrait;
}
