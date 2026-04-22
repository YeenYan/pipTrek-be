<?php

namespace Src\Modules\Tags\GraphQL\Resolvers;

use Src\Modules\Tags\Application\Services\TagService;

class TagResolver
{
 public function __construct(private readonly TagService $tagService) {}

 public function tags($_, array $args): array
 {
     return $this->tagService->getAllTags();
 }

public function tag($_, array $args)
{
     return $this->tagService->getTag($args['tag_name']);
}
 public function createTag($_, array $args): array
 {
     return $this->tagService->createTag($args['input'] ?? $args);
 }

 public function updateTag($_, array $args): array
 {
     return $this->tagService->updateTag($args['id'], $args['input'] ?? $args);
 }

 public function deleteTag($_, array $args): array
 {
     return $this->tagService->deleteTag($args['id']);
 }
}