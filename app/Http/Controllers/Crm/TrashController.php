<?php

namespace App\Http\Controllers\Crm;

use App\Enums\Page;
use App\Enums\ToastType;
use App\Enums\TrashType;
use App\Exceptions\RestoreBlockedException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class TrashController extends Controller
{
    public function deals(): Response
    {
        return $this->render(TrashType::Deals);
    }

    public function contacts(): Response
    {
        return $this->render(TrashType::Contacts);
    }

    public function companies(): Response
    {
        return $this->render(TrashType::Companies);
    }

    public function tickets(): Response
    {
        return $this->render(TrashType::Tickets);
    }

    public function restore(TrashType $type, string $hash): RedirectResponse
    {
        try {
            $type->bin()->restore($hash);
        } catch (RestoreBlockedException $blocked) {
            $this->toast($blocked->getMessage(), ToastType::Error);

            return back();
        }

        $this->toast('Record restored.');

        return back();
    }

    public function destroy(TrashType $type, string $hash): RedirectResponse
    {
        $type->bin()->purge($hash);

        $this->toast('Record permanently deleted.');

        return back();
    }

    private function render(TrashType $type): Response
    {
        return $this->inertia(Page::TrashIndex, [
            'type' => $type,
            'items' => $type->bin()->items(),
        ]);
    }
}
