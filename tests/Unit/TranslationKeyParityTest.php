<?php

declare(strict_types=1);

it('has at least two available locales', function (): void {
    expect(__DIR__ . '/../../resources/lang')->toHaveAtLeastTwoLocales('vendra-user');
});

it('keeps translation files and keys in sync across locales', function (): void {
    expect(__DIR__ . '/../../resources/lang')->toHaveTranslationsInSync('vendra-user');
});

it('keeps translation file keys sorted', function (): void {
    expect(__DIR__ . '/../../resources/lang')->toHaveSortedTranslationKeys('vendra-user');
});
