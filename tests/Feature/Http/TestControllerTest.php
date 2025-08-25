<?php

it('has a route', function () {
    $response = $this->get('ifs-test')
                    ->assertSeeText('IFS Laravel Package');
});