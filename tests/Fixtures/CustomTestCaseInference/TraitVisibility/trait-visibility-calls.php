<?php

declare(strict_types=1);

use Tests\Type\Fixtures\CustomTestCase;
use Tests\Type\Fixtures\VisibilityTrait;

uses(CustomTestCase::class, VisibilityTrait::class);

it('can call protected and private trait methods on $this', function (): void {
    $this->protectedTraitMethod();
    $this->privateTraitMethod();
});

it('can call protected and private static trait methods through $this', function (): void {
    $this::protectedStaticTraitMethod();
    $this::privateStaticTraitMethod();
});

beforeEach(function (): void {
    $this->privateTraitMethod();
    $this::privateStaticTraitMethod();
});
