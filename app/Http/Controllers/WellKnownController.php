<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use JsonException;

class WellKnownController extends Controller
{
    public function appleAppSiteAssociation(): Response
    {
        return $this->jsonFile('apple-app-site-association');
    }

    public function assetLinks(): Response
    {
        return $this->jsonFile('assetlinks.json');
    }

    private function jsonFile(string $fileName): Response
    {
        $path = public_path('.well-known/'.$fileName);
        abort_unless(is_file($path), 404);

        $contents = (string) file_get_contents($path);

        try {
            json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            abort(500, 'Invalid well-known JSON document.');
        }

        return response($contents, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
