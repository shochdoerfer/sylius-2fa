<?php

/*
 * This file is part of the Sylius 2FA Auth package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Tests\BitExpert\SyliusTwoFactorAuthPlugin\Unit\Form\DataTransformer;

use BitExpert\SyliusTwoFactorAuthPlugin\Form\DataTransformer\VerificationCodeTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VerificationCodeTransformerTest extends TestCase
{
    private VerificationCodeTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new VerificationCodeTransformer();
    }

    #[Test]
    public function transformReturnsStringArrayWhenValueIsProvided(): void
    {
        $value = '123456';
        $result = $this->transformer->transform($value);

        self::assertCount(6, $result);
        self::assertEquals(['1', '2', '3', '4', '5', '6'], $result);
    }

    #[Test]
    public function transformReturnsArrayOfEmptyStringsWhenValueIsEmpty(): void
    {
        $result = $this->transformer->transform('');

        self::assertCount(6, $result);
        self::assertEquals(['', '', '', '', '', ''], $result);
    }

    #[Test]
    public function transformReturnsArrayOfEmptyStringsWhenValueIsNull(): void
    {
        $result = $this->transformer->transform(null);

        self::assertCount(6, $result);
        self::assertEquals(['', '', '', '', '', ''], $result);
    }

    #[Test]
    public function transformHandlesLongerVerificationCodes(): void
    {
        $value = '1234567890';
        $result = $this->transformer->transform($value);

        self::assertCount(10, $result);
        self::assertEquals(['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'], $result);
    }

    #[Test]
    public function transformHandlesShorterVerificationCodes(): void
    {
        $value = '123';
        $result = $this->transformer->transform($value);

        self::assertCount(3, $result);
        self::assertEquals(['1', '2', '3'], $result);
    }

    #[Test]
    public function reverseTransformReturnsStringWhenArrayIsProvided(): void
    {
        $value = ['1', '2', '3', '4', '5', '6'];
        $result = $this->transformer->reverseTransform($value);

        self::assertEquals('123456', $result);
    }

    #[Test]
    public function reverseTransformReturnsEmptyStringWhenValueIsNull(): void
    {
        $result = $this->transformer->reverseTransform(null);

        self::assertEmpty($result);
    }

    #[Test]
    public function reverseTransformHandlesEmptyArray(): void
    {
        $value = [];
        $result = $this->transformer->reverseTransform($value);

        self::assertEmpty($result);
    }

    #[Test]
    public function reverseTransformWorksWithNonNumericCharacters(): void
    {
        $value = ['a', 'b', 'c', 'd', 'e', 'f'];
        $result = $this->transformer->reverseTransform($value);

        self::assertEquals('abcdef', $result);
    }
}
