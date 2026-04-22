<?php

namespace Src\Modules\Tags\Application\Services;

use Src\Modules\Tags\Application\Exceptions\TagException;
use Src\Modules\Tags\Infrastructure\Repositories\TagRepository;

class TagService
{
 public function __construct(private readonly TagRepository $repository)
 {}

 public function createTag(array $data): array
 {
    // 1. Normalize input
    $tagName = strtolower(trim($data['tag_name']));

    // 2. Check if tag already exists
    $existingTag = $this->repository->findTagByName($tagName);

    if ($existingTag) {
        return [
            'tag' => $existingTag,
            'message' => 'Tag already exists.',
        ];
    }

    // Create new tag
    $tag = $this->repository->createTag([
      'tag_name' => $tagName,
    ]);

     return [
      'tag' => $tag,
      'message' => 'Tag created successfully.',
     ];
 }

 public function getAllTags(): array 
 {
     return $this->repository->findAllTags();
 }

public function getTag(string $tagName)
{
    // 1. Validate input
    if (empty($tagName)) {
        throw new TagException('tag_name is required.');
    }

    // 2. Normalize
    $tagName = strtolower(trim($tagName));

    // 3. Find tag
    $tag = $this->repository->findTagByName($tagName);

    // 4. Handle not found
    if (!$tag) {
        throw new TagException('Tag not found.');
    }

    return $tag;
}

 public function updateTag(string $id, array $data): array
 {
     $tag = $this->repository->findTagById($id);

     if (!$tag) {
         throw new TagException('Tag not found.');
     }

     $updatedTag = $this->repository->updateTag($tag, $data);

     return [
         'tag' => $updatedTag,
         'message' => 'Tag updated successfully.',
     ];
 }

 public function deleteTag(string $id): array
 {
     $tag = $this->repository->findTagById($id);

     if (!$tag) {
         throw new TagException('Tag not found.');
     }

     $this->repository->deleteTag($tag);

     return [
         'message' => 'Tag deleted successfully.',
     ];
 }

}