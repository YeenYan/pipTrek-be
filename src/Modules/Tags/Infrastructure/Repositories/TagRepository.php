<?php

namespace Src\Modules\Tags\Infrastructure\Repositories;

use Src\Modules\Tags\Domain\Tag; 

class TagRepository
{
    public function createTag(array $data)
    {
        return Tag::create($data);
    }

    public function findAllTags(): array
    {
        return Tag::all()->all();
    }

    public function findTagByName(string $name): ?Tag
    {
        return Tag::whereRaw('LOWER(tag_name) = ?', [strtolower($name)])->first();
    }

    public function findTagById(string $id): ?Tag
    {
        return Tag::find($id);
    }

    public function updateTag(Tag $tag, array $data): Tag
    {
        $tag->update($data);
        return $tag->fresh();
    }

    public function deleteTag(Tag $tag): void
    {
        $tag->delete();
    }

} 