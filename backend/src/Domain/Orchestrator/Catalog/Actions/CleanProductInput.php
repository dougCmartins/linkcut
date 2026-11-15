<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Catalog\Actions;

use Domain\Catalog\Data\ProductInputData;
use Domain\Shared\Support\PlainText;
use Illuminate\Validation\ValidationException;

/** Trims the name and strips links and tags from the description. An empty description becomes null. */
final class CleanProductInput
{
    public function handle(ProductInputData $data): ProductInputData
    {
        $name = trim($data->name);
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'required']);
        }

        return new ProductInputData(
            name: $name,
            price_in_cents: $data->price_in_cents,
            description: PlainText::text($data->description),
            image_id: $data->image_id,
            is_active: $data->is_active,
        );
    }
}
