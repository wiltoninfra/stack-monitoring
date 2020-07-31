<?php

namespace Tests\Routes\v1\Tags;

use Tests\TestCase;
use Illuminate\Http\Response;
use Tests\Routes\v1\Tags\TagFactory;

class TagTest extends TestCase
{
    public $factory = null;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new TagFactory($this->app);
    }

    /**
     * @group tag
     */
    public function testShouldReturnAllTags()
    {
        $tag = $this->factory->create();

        $this->get(route('tags.getAll'));

        $this->seeStatusCode(Response::HTTP_OK);

        $this->seeJsonStructure(['*' =>
            [
                'id',
                'name',
                'abbreviation',
                'color'
            ]
        ]);

        $this->factory->delete($tag->getId());
    }

    /**
     * @group tag
     */
    public function testShouldCreateTag()
    {
        $parameters = $this->factory->getDocument();

        $this->post(route('tags.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'name',
            'abbreviation',
            'color'
        ]);

        $this->seeJson($parameters);

        $this->factory->delete($this->response->getOriginalContent()->getId());
    }

    /**
     * @group tags
     */
    public function testShouldNotCreateTagEmpty()
    {
        $parameters = [];

        $this->post(route('tags.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @group tags
     */
    public function testShouldNotCreateTagWithAbbreviationLessThen3()
    {
        $parameters = $this->factory->getDocument([
            'abbreviation' => 'TS'
        ]);

        $this->post(route('tags.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @group tags
     */
    public function testShouldNotCreateTagWithAbbreviationBiggerThen3()
    {
        $parameters = $this->factory->getDocument([
            'abbreviation' => 'TSTT'
        ]);

        $this->post(route('tags.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @group tags
     */
    public function testShouldNotCreateTagDuplicatedAbbreviationColor()
    {
        
        $tag = $this->factory->create();
        
        $parameters = $this->factory->getDocument();

        $this->post(route('tags.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

        $this->factory->delete($tag->getId());
    }
    

    /**
     * @group tags
     */
    public function testShouldUpdateTag ()
    {
        $tag = $this->factory->create();

        $parameters = [
            "name" => "UPDATED",
            "abbreviation" => "TS2",
            "color" => "#FF2"
        ];

        $this->put(route('tags.update', ['tag_id' => $tag->getId()]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonStructure([
            'id',
            'name',
            'abbreviation',
            'color'
        ]);

        $this->seeJson($parameters);

        $this->factory->delete($tag->getId());
    }
}
