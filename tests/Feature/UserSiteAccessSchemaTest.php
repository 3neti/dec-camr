<?php

use Illuminate\Support\Facades\Schema;

test('site access keeps a composite lookup index for user and site', function () {
    $indexes = collect(Schema::getIndexes('user_access_group'));

    expect($indexes->contains(fn (array $index): bool => $index['columns'] === ['user_idx', 'site_idx']))->toBeTrue();
});
