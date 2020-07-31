<?php

namespace Promo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Promo\Services\TagService;
use Promo\Http\Requests\TagRequest;
use Promo\Http\Resources\TagResource;
use Illuminate\Support\Facades\Validator;

class TagController
{
    private $tag_service;

    public function __construct(TagService $tag_service)
    {
        $this->tag_service = $tag_service;
    }

    /**
     * Obtém todos as tags
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Get(
     *     path="/tags",
     *     description="Obtém tags",
     *     produces={"application/json"},
     *     tags={"tag"},
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function getAll(Request $request)
    {
        $tags = $this->tag_service->getAll();

        return TagResource::collection($tags)
            ->response();
    }

    /**
     * Cria tag
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Post(
     *     path="/tags",
     *     description="Cria tag",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"tag"},
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/Tag"),
     *     ),
     *     @SWG\Response(response=201, description="Created"),
     *     @SWG\Response(response=422, description="Validation concern"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     * @throws \Promo\Exceptions\ValidationException
     */
    public function create(Request $request)
    {
        Validator::make($request->all(), TagRequest::rules())
            ->validate();

        $tag = $this->tag_service->create($request->all());

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Atualiza tag
     *
     * @param \Illuminate\Http\Request $request
     * @param string $tag_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Promo\Exceptions\ValidationException
     * @SWG\Put(
     *     path="/tags/{tag_id}",
     *     description="Atualiza tag",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"tag"},
     *     @SWG\Parameter(
     *         name="tag_id",
     *         in="path",
     *         description="Id da tag",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/Tag"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation concern"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function update(Request $request, string $tag_id)
    {
        Validator::make($request->all(), TagRequest::rules())
            ->validate();

        $tag = $this->tag_service->update($tag_id, $request->all());

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Exclui tag
     *
     * @param \Illuminate\Http\Request $request
     * @param string $tag_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Delete(
     *     path="/tags/{tag_id}",
     *     description="Exclui tag (soft delete)",
     *     produces={"application/json"},
     *     tags={"tag"},
     *     @SWG\Parameter(
     *         name="tag_id",
     *         in="path",
     *         description="Id da tag",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=204, description="Deleted"),
     *     @SWG\Response(response=404, description="Tag not found"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function delete(Request $request, string $tag_id)
    {
        $this->tag_service->delete($tag_id);
        return response(null, Response::HTTP_NO_CONTENT);
    }
}