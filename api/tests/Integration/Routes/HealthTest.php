<?php

namespace Tests\Routes;

use Tests\TestCase;

class HealthTest extends TestCase
{
    /**
     * Caso de teste da rota /health
     *
     * @return void
     */
    public function testHealth()
    {
        $this->get('/health');

        $this->assertEquals(
            'up', $this->response->getContent()
        );
    }
}
