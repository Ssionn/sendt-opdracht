<?php

declare(strict_types=1);

test('the root path redirects to /exceptions', function (): void {
    $response = $this->get('/');

    $response->assertRedirect('/exceptions');
});
